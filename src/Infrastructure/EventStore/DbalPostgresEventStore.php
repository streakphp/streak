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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Statement;
use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Event\Stream;
use Streak\Domain\EventStore;
use Streak\Domain\Exception;
use Streak\Domain\Id\UUID;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class DbalPostgresEventStore implements \Iterator, EventStore, Event\Stream, Schemable, Schema
{
    private const POSTGRES_PLATFORM_NAME = 'postgresql';

    private const DIRECTION_FORWARD = 'forward';
    private const DIRECTION_BACKWARD = 'backward';

    private $connection;
    private $converter;

    private $current = false;
    private $key = 0;

    /**
     * @var \PDOStatement
     */
    private $statement;

    private $filter = [];
    private $only = [];
    private $without = [];
    private $from;
    private $to;
    private $after;
    private $before;
    private $limit;

    public function __construct(Connection $connection, Event\Converter $converter)
    {
        $this->connection = $connection;
        $this->converter = $converter;
        $this->filter = new EventStore\Filter();
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \RuntimeException
     */
    public function checkPlatform()
    {
        $platform = $this->connection->getDatabasePlatform();

        if (self::POSTGRES_PLATFORM_NAME !== $platform->getName()) {
            throw new \RuntimeException('Only PostgreSQL database is supported by selected event store.');
        }
    }

    /**
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \RuntimeException
     */
    public function create() : void
    {
        $this->checkPlatform();
        $this->connection->beginTransaction();

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
        $sqls[] = 'CREATE INDEX ON events (type, producer_type, producer_id);';
        $sqls[] = 'CREATE INDEX ON events (number, type, producer_type, producer_id);';

        foreach ($sqls as $sql) {
            $statement = $this->connection->prepare($sql);
            $statement->execute();
        }

        $this->connection->commit();
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \RuntimeException
     */
    public function drop() : void
    {
        $this->checkPlatform();

        $sql = 'DROP TABLE IF EXISTS events';
        $statement = $this->connection->prepare($sql);
        $statement->execute();
    }

    public function schema() : ?Schema
    {
        return $this;
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
            $statement = $this->connection->prepare($sql);
            $statement->execute($parameters);
        } catch (UniqueConstraintViolationException $e) {
            // check for constraint names only, as message may be localised
            if (false !== mb_strpos($e->getMessage(), '"events_producer_type_producer_id_producer_version_key"')) {
                throw new Exception\ConcurrentWriteDetected($producerId);
            }
            throw $e;
        }

        while ($returned = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $sequence = (string) $returned['number'];
            $uuid = $returned['uuid'];
            $uuid = mb_strtoupper($uuid);
            $uuid = new UUID($uuid);
            $metadata = $returned['metadata'];
            $metadata = json_decode($metadata, true);
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

    public function from(Event $event) : Event\Stream
    {
        $stream = $this->copy();
        $stream->from = $event;
        $stream->after = null;

        return $stream;
    }

    public function to(Event $event) : Event\Stream
    {
        $stream = $this->copy();
        $stream->to = $event;
        $stream->before = null;

        return $stream;
    }

    public function after(Event $event) : Event\Stream
    {
        $stream = $this->copy();
        $stream->from = null;
        $stream->after = $event;

        return $stream;
    }

    public function before(Event $event) : Event\Stream
    {
        $stream = $this->copy();
        $stream->to = null;
        $stream->before = $event;

        return $stream;
    }

    public function stream(?EventStore\Filter $filter = null) : Event\Stream
    {
        $stream = $this->copy();
        if (null !== $filter) {
            $stream->filter = $filter;
        }

        return $stream;
    }

    public function only(string ...$types) : Event\Stream
    {
        $stream = $this->copy();
        $stream->only = $types;
        $stream->without = [];

        // TODO: check if type is Domain\Id

        return $stream;
    }

    public function without(string ...$types) : Event\Stream
    {
        $stream = $this->copy();
        $stream->without = $types;
        $stream->only = [];

        // TODO: check if type is Domain\Id

        return $stream;
    }

    public function limit(int $limit) : Stream
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
            $this->filter,
            self::DIRECTION_FORWARD,
            [],
            $this->from,
            $this->to,
            $this->after,
            $this->before,
            $this->only,
            $this->without,
            1,
            null
        );

        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if (false === $row) {
            return null;
        }

        $event = $this->fromRow($row);

        return $event;
    }

    public function last() : ?Event
    {
        $direction = self::DIRECTION_BACKWARD;
        $limit = 1;
        $offset = null;

        if (null !== $this->limit) {
            $direction = self::DIRECTION_FORWARD;
            $limit = $this->limit;
            $offset = $this->limit - 1;
        }

        $statement = $this->select(
            $this->filter,
            $direction,
            [],
            $this->from,
            $this->to,
            $this->after,
            $this->before,
            $this->only,
            $this->without,
            $limit,
            $offset
        );

        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if (false === $row) {
            return null;
        }

        $event = $this->fromRow($row);

        return $event;
    }

    public function empty() : bool
    {
        $statement = $this->select(
            $this->filter,
            null, // we don't need ORDER BY here
            [],
            $this->from,
            $this->to,
            $this->after,
            $this->before,
            $this->only,
            $this->without,
            1,
            null
        );

        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if (false === $row) {
            return true;
        }

        return false;
    }

    public function current() : Event
    {
        $event = $this->fromRow($this->current);

        return $event;
    }

    public function next()
    {
        $this->current = $this->statement->fetch(\PDO::FETCH_ASSOC);
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
            $this->filter,
            self::DIRECTION_FORWARD,
            [],
            $this->from,
            $this->to,
            $this->after,
            $this->before,
            $this->only,
            $this->without,
            $this->limit,
            null
        );
        $this->current = $this->statement->fetch(\PDO::FETCH_ASSOC);
        $this->key = 0;
    }

    private function count() : int
    {
        $statement = $this->select(
            $this->filter,
            null,
            ['COUNT(*)'],
            $this->from,
            $this->to,
            $this->after,
            $this->before,
            $this->only,
            $this->without,
            null,
            null
        );

        $count = $statement->fetchColumn(0);

        return (int) $count;
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
        $stream = new self($this->connection, $this->converter);
        $stream->from = $this->from;
        $stream->to = $this->to;
        $stream->after = $this->after;
        $stream->before = $this->before;
        $stream->limit = $this->limit;
        $stream->filter = $this->filter;
        $stream->only = $this->only;
        $stream->without = $this->without;

        return $stream;
    }

    private function select(
        EventStore\Filter $filter,
        ?string $direction,
        ?array $columns,
        ?Event $from,
        ?Event $to,
        ?Event $after,
        ?Event $before,
        ?array $only,
        ?array $without,
        ?int $limit,
        ?int $offset
    ) : Statement {
        $columns = implode(' , ', $columns);

        if (!$columns) {
            $columns = '*';
        }

        $sql = "SELECT {$columns} FROM events ";
        $where = [];
        $parameters = [];

        if (count($filter->producerIds()) > 0) {
            /* @var $filter Domain\Id[] */
            $sub = [];
            foreach ($filter->producerIds() as $key => $id) {
                $sub[] = " (producer_type = :producer_type_$key AND producer_id = :producer_id_$key) ";
                $parameters["producer_type_$key"] = get_class($id);
                $parameters["producer_id_$key"] = $id->toString();
            }
            $where[] = '('.implode(' OR ', $sub).')';
        }

        if (count($filter->producerTypes()) > 0) {
            /* @var $filter Domain\Id[] */
            $sub = [];
            foreach ($filter->producerTypes() as $key => $type) {
                $sub[] = " (producer_type = :only_producer_type_$key) ";
                $parameters["only_producer_type_$key"] = $type;
            }
            $where[] = '('.implode(' OR ', $sub).')';
        }

        if ($only) {
            /* @var $only string[] */
            $sub = [];
            foreach ($only as $key => $type) {
                $sub[] = " (type = :include_type_$key) ";
                $parameters["include_type_$key"] = $type;
            }
            $where[] = '('.implode(' OR ', $sub).')';
        }

        if ($without) {
            /* @var $without string[] */
            $sub = [];
            foreach ($without as $key => $type) {
                $sub[] = " (type != :exclude_type_$key) ";
                $parameters["exclude_type_$key"] = $type;
            }
            $where[] = '('.implode(' AND ', $sub).')';
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

        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);

        return $statement;
    }

    private function fromRow($row) : Event
    {
        // TODO: use identity map
        $event = $row['body'];
        $event = json_decode($event, true);
        $event = $this->converter->arrayToEvent($event);

        $metadata = $row['metadata'];
        $metadata = json_decode($metadata, true);
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
            'body' => json_encode($this->converter->eventToArray($event)),
            'metadata' => json_encode($metadata->toArray()),
            'producer_type' => get_class($producerId),
            'producer_id' => $producerId->toString(),
            'producer_version' => $version,
        ];

        return $row; // put metadata back
    }
}
