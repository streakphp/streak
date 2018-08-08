<?php

/**
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Streak\Infrastructure\EventStore;

use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Event\FilterableStream;
use Streak\Domain\EventStore;
use Streak\Domain\Exception;
use Streak\Domain\Id\UUID;
use Streak\Infrastructure\Serializer\JsonSerializer;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class PDOPostgresEventStore implements EventStore, Event\Log, Event\FilterableStream
{
    private const DIRECTION_FORWARD = 'forward';
    private const DIRECTION_BACKWARD = 'backward';

    private $pdo;
    private $serializer;

    private $current = false;
    private $key = 0;

    /**
     * @var \PDOStatement
     */
    private $statement;

    private $producers = [];
    private $types = [];
    private $from;
    private $to;
    private $after;
    private $before;
    private $limit;

    public function __construct(\PDO $pdo, JsonSerializer $serializer)
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute($pdo::ATTR_ERRMODE, $pdo::ERRMODE_EXCEPTION);
        $this->serializer = $serializer;
    }

    public function create()
    {
        $this->pdo->beginTransaction();

        $sqls[] = <<<SQL
CREATE TABLE IF NOT EXISTS events (
  number BIGSERIAL,
  uuid UUID NOT NULL,
  type VARCHAR(256) NOT NULL,
  body JSONB NOT NULL,
  metadata JSONB NOT NULL,
  producer_type VARCHAR(256) NOT NULL,
  producer_id VARCHAR(256) NOT NULL,
  producer_version INT,
  appended_at timestamp NOT NULL DEFAULT NOW(),
  PRIMARY KEY(number),
  UNIQUE (number),
  UNIQUE (uuid),
  UNIQUE (producer_type, producer_id, producer_version)
);
SQL;
        $sqls[] = 'CREATE INDEX ON events (producer_type, producer_id);';
        $sqls[] = 'CREATE INDEX ON events (producer_type, producer_id, producer_version);';

        foreach ($sqls as $sql) {
            $statement = $this->pdo->prepare($sql);
            $statement->execute();
        }

        $this->pdo->commit();
    }

    public function drop()
    {
        $sql = 'DROP TABLE IF EXISTS events';
        $statement = $this->pdo->prepare($sql);
        $statement->execute();
    }

    public function producerId(Event $event) : Domain\Id
    {
        $metadata = Event\Metadata::fromObject($event);

        if ($metadata->empty()) {
            throw new Exception\EventNotInStore($event);
        }

        $producerType = $metadata->get('producer_type');
        $producerId = $metadata->get('producer_id');

        $reflection = new \ReflectionClass($producerType);

        if (!$reflection->implementsInterface(Domain\Id::class)) {
            throw new \InvalidArgumentException(); // TODO: domain exception here
        }

        $method = $reflection->getMethod('fromString');

        return $method->invoke(null, $producerId);
    }

    public function add(Domain\Id $producerId, ?int $version, Event ...$events) : void
    {
        if (0 === count($events)) {
            return;
        }

        $sql = 'INSERT INTO events (uuid, type, body, metadata, producer_type, producer_id, producer_version) ';

        $parameters = [];
        $values = [];
        $transaction = new \SplObjectStorage();
        foreach ($events as $key => $event) {
            $metadata = Event\Metadata::fromObject($event);

            if (!$metadata->empty()) {
                throw new Exception\EventAlreadyInStore($event);
            }

            $uuid = UUID::create();

            $version = $this->bumpUp($version);
            $row = $this->toRow($producerId, $version, $uuid, $event);

            // TODO: if version set, maybe throw Exception\EventAlreadyStored exception here?

            $placeholders = [];
            foreach ($row as $column => $value) {
                $parameterName = $column.'_'.$key;
                $parameters[$parameterName] = $value;
                $placeholder = ':'.$parameterName;
                $placeholders[] = $placeholder;
            }

            $values[] = '('.implode(',', $placeholders).')';
            $transaction[$uuid] = $event;
        }

        $values = implode(',', $values);

        $sql = "$sql VALUES $values";
        $sql = "$sql RETURNING number, uuid, metadata, producer_version";

        try {
            $statement = $this->pdo->prepare($sql);
            $statement->execute($parameters);
        } catch (\PDOException $e) {
            $code = (string) $e->getCode();
            $message = $e->getMessage();
            if ('23505' === $code) {
                // check for constraint names only, as message may be localised
                if (false !== mb_strpos($message, '"events_producer_type_producer_id_producer_version_key"')) {
                    throw new Exception\ConcurrentWriteDetected($producerId);
                }
            }

            throw $e;
        }

        while ($returned = $statement->fetch($this->pdo::FETCH_ASSOC)) {
            $sequence = (string) $returned['number'];
            $uuid = $returned['uuid'];
            $uuid = mb_strtoupper($uuid);
            $uuid = new UUID($uuid);
            $metadata = $returned['metadata'];
            $metadata = $this->serializer->unserialize($metadata);
            $metadata = Event\Metadata::fromArray($metadata);

            foreach ($transaction as $current) {
                $event = $transaction->getInfo();
                if ($uuid->equals($current)) {
                    $metadata->set('sequence', $sequence);
                    $metadata->toObject($event);
                    continue 2;
                }
            }
        }
    }

    public function from(Event $event) : Event\FilterableStream
    {
        $stream = $this->copy();
        $stream->from = $event;
        $stream->after = null;

        return $stream;
    }

    public function to(Event $event) : Event\FilterableStream
    {
        $stream = $this->copy();
        $stream->to = $event;
        $stream->before = null;

        return $stream;
    }

    public function after(Event $event) : Event\FilterableStream
    {
        $stream = $this->copy();
        $stream->from = null;
        $stream->after = $event;

        return $stream;
    }

    public function before(Event $event) : Event\FilterableStream
    {
        $stream = $this->copy();
        $stream->to = null;
        $stream->before = $event;

        return $stream;
    }

    public function streamFor(Domain\Id ...$producers) : Event\FilterableStream
    {
        $stream = $this->copy();
        $stream->producers = $producers;

        return $stream;
    }

    public function stream(Domain\Id ...$producers) : Event\FilterableStream
    {
        return $this->streamFor(...$producers);
    }

    public function of(string ...$types) : Event\FilterableStream
    {
        $stream = $this->copy();
        $stream->types = $types;

        // TODO: check if type is Domain\Id

        return $stream;
    }

    public function limit(int $limit) : FilterableStream
    {
        $count = $this->count();

        if ($limit > $count) {
            $limit = $count;
        }

        $stream = $this->copy();
        $stream->limit = $limit;

        return $stream;
    }

    public function first() : ?Event
    {
        $statement = $this->select(
            self::DIRECTION_FORWARD,
            [],
            $this->from,
            $this->to,
            $this->after,
            $this->before,
            $this->producers,
            $this->types,
            1,
            null
        );

        $row = $statement->fetch($this->pdo::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $event = $this->fromRow($row);

        return $event;
    }

    public function last() : ?Event
    {
        if (null === $this->limit) {
            $direction = self::DIRECTION_BACKWARD;
            $limit = 1;
            $offset = null;
        } elseif (null !== $this->limit) {
            $direction = self::DIRECTION_FORWARD;
            $limit = $this->limit;
            $offset = $this->limit - 1;
        }

        $statement = $this->select(
            $direction,
            [],
            $this->from,
            $this->to,
            $this->after,
            $this->before,
            $this->producers,
            $this->types,
            $limit,
            $offset
        );

        $row = $statement->fetch($this->pdo::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $event = $this->fromRow($row);

        return $event;
    }

    public function count() : int
    {
        $statement = $this->select(
            null,
            ['COUNT(number)'],
            $this->from,
            $this->to,
            $this->after,
            $this->before,
            $this->producers,
            $this->types,
            null,
            null
        );

        $count = $statement->fetchColumn(0);

        return $count;
    }

    public function empty() : bool
    {
        return 0 === $this->count();
    }

    public function current() : Event
    {
        $event = $this->fromRow($this->current);

        return $event;
    }

    public function next()
    {
        $this->current = $this->statement->fetch($this->pdo::FETCH_ASSOC);
        $this->key = $this->key + 1;
    }

    public function key()
    {
        return $this->key;
    }

    public function valid()
    {
        return false !== $this->current;
    }

    public function rewind()
    {
        $this->statement = $this->select(
            self::DIRECTION_FORWARD,
            [],
            $this->from,
            $this->to,
            $this->after,
            $this->before,
            $this->producers,
            $this->types,
            $this->limit,
            null
        );
        $this->current = $this->statement->fetch($this->pdo::FETCH_ASSOC);
        $this->key = 0;
    }

    public function log() : Event\Log
    {
        return $this;
    }

    private function bumpUp(?int $version) : ?int
    {
        if (null === $version) {
            return null;
        }

        return ++$version;
    }

    private function copy() : self
    {
        $stream = new self($this->pdo, $this->serializer);
        $stream->from = $this->from;
        $stream->to = $this->to;
        $stream->after = $this->after;
        $stream->before = $this->before;
        $stream->limit = $this->limit;
        $stream->producers = $this->producers;
        $stream->types = $this->types;

        return $stream;
    }

    private function select(
        ?string $direction,
        ?array $columns,
        ?Event $from,
        ?Event $to,
        ?Event $after,
        ?Event $before,
        ?array $producers,
        ?array $types,
        ?int $limit,
        ?int $offset
    ) : \PDOStatement {
        $columns = implode(' , ', $columns);

        if (!$columns) {
            $columns = '*';
        }

        $sql = "SELECT {$columns} FROM events ";
        $where = [];
        $parameters = [];

        if ($producers) {
            /* @var $producers Domain\Id[] */
            $sub = [];
            foreach ($producers as $key => $id) {
                $sub[] = " (producer_type = :producer_type_$key AND producer_id = :producer_id_$key) ";
                $parameters["producer_type_$key"] = get_class($id);
                $parameters["producer_id_$key"] = $id->toString();
            }
            $where[] = '('.implode(' OR ', $sub).')';
        }

        if ($types) {
            /* @var $types string[] */
            $sub = [];
            foreach ($types as $key => $type) {
                $sub[] = " (type = :type_$key) ";
                $parameters["type_$key"] = $type;
            }
            $where[] = '('.implode(' OR ', $sub).')';
        }

        if ($from) {
            $where[] = ' (number >= :from) ';
            $parameters['from'] = Event\Metadata::fromObject($from)->get('sequence');
        }

        if ($to) {
            $where[] = ' (number <= :to) ';
            $parameters['to'] = Event\Metadata::fromObject($to)->get('sequence');
        }

        if ($after) {
            $where[] = ' (number > :after) ';
            $parameters['after'] = Event\Metadata::fromObject($after)->get('sequence');
        }

        if ($before) {
            $where[] = ' (number < :before) ';
            $parameters['before'] = Event\Metadata::fromObject($before)->get('sequence');
        }

        $where = implode(' AND ', $where);

        if ($where) {
            $sql = "$sql WHERE $where ";
        }

        if (self::DIRECTION_FORWARD === $direction) {
            $sql .= ' ORDER BY number ASC ';
        }

        if (self::DIRECTION_BACKWARD === $direction) {
            $sql .= ' ORDER BY number DESC ';
        }

        if ($offset) {
            if ($offset >= 0) {
                $sql .= " OFFSET {$offset} ";
            }
        }

        if ($limit) {
            $sql .= " LIMIT {$limit} ";
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);

        return $statement;
    }

    private function fromRow($row) : Event
    {
        // TODO: use identity map
        $event = $row['body'];
        $event = $this->serializer->unserialize($event);

        $metadata = $row['metadata'];
        $metadata = $this->serializer->unserialize($metadata);
        $metadata = Event\Metadata::fromArray($metadata);
        $metadata->set('sequence', (string) $row['number']);

        $metadata->toObject($event);

        return $event;
    }

    private function toRow(Domain\Id $producerId, ?int $version, UUID $uuid, $event) : array
    {
        $metadata = Event\Metadata::fromObject($event);
        $metadata::clear($event);
        $metadata->set('producer_type', get_class($producerId));
        $metadata->set('producer_id', $producerId->toString());

        $row = [
            'uuid' => $uuid->toString(),
            'type' => get_class($event),
            'body' => $this->serializer->serialize($event),
            'metadata' => $this->serializer->serialize($metadata->toArray()),
            'producer_type' => get_class($producerId),
            'producer_id' => $producerId->toString(),
            'producer_version' => $version,
        ];

        return $row; // put metadata back
    }
}
