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

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class PDOPostgresEventStore implements EventStore, Event\Log, Event\FilterableStream
{
    // TODO: make it configurable
    private const EVENT_LOG_TABLE = 'events';
    private const EVENT_LOG_SEQUENCE_COLUMN = 'number';
    private const EVENT_LOG_PRODUCER_TYPE_COLUMN = 'producer_type';
    private const EVENT_LOG_PRODUCER_ID_COLUMN = 'producer_id';
    private const EVENT_LOG_PRODUCER_VERSION_COLUMN = 'producer_version';
    private const EVENT_LOG_EVENT_UUID_COLUMN = 'uuid';
    private const EVENT_LOG_EVENT_TYPE_COLUMN = 'type';
    private const EVENT_LOG_EVENT_BODY_COLUMN = 'body';
    private const EVENT_LOG_EVENT_METADATA_COLUMN = 'metadata';
    private const EVENT_LOG_EVENT_APPENDED_AT_COLUMN = 'appended_at';

    private const DIRECTION_FORWARD = 'forward';
    private const DIRECTION_BACKWARD = 'backward';

    private $pdo;
    private $converter;

    private $current = false;
    private $key = 0;

    /**
     * @var \PDOStatement
     */
    private $statement;

    private $producers = [];
    private $from;
    private $to;
    private $after;
    private $before;
    private $limit;

    public function __construct(
        \PDO $pdo,
        Event\Converter $converter
    ) {
        $this->pdo = $pdo;
        $this->pdo->setAttribute($pdo::ATTR_ERRMODE, $pdo::ERRMODE_EXCEPTION);
        $this->converter = $converter;
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

    public function add(Domain\Id $producerId, ?Event $last = null, Event ...$events) : void
    {
        if (0 === count($events)) {
            return;
        }

        if (null !== $last) {
            $metadata = Event\Metadata::fromObject($last);

            if (!$metadata->has('version')) {
                throw new Exception\EventNotInStore($last);
            }

            $version = $metadata->get('version', '0');
            $version = (int) $version;
        } else {
            $version = 0;
        }

        $sql = 'INSERT INTO events (uuid, type, body, metadata, producer_type, producer_id, producer_version) ';

        $parameters = [];
        $values = [];
        foreach ($events as $key => $event) {
            ++$version;
            $metadata = Event\Metadata::fromObject($event);
            if (!$metadata->has('version')) {
                $metadata->set('version', (string) $version);
                $metadata->toObject($event);
            }

            $row = $this->toRow($producerId, $version, $event);

            // TODO: if version set, maybe throw Exception\EventAlreadyStored exception here?

            $placeholders = [];
            foreach ($row as $column => $value) {
                $parameterName = $column.'_'.$key;
                $parameters[$parameterName] = $value;
                $placeholder = ':'.$parameterName;
                $placeholders[] = $placeholder;
            }

            $values[] = '('.implode(',', $placeholders).')';
        }

        $values = implode(',', $values);

        $sql = "$sql VALUES $values";
        $sql = "$sql RETURNING number, uuid, producer_version";

        try {
            $statement = $this->pdo->prepare($sql);
            $statement->execute($parameters);
        } catch (\PDOException $e) {
            if ('23505' === $e->getCode()) {
                if (false !== mb_strpos($e->getMessage(), 'ERROR:  duplicate key value violates unique constraint "events_producer_type_producer_id_producer_version_key"')) {
                    throw new Exception\ConcurrentWriteDetected($producerId);
                }
                if (false !== mb_strpos($e->getMessage(), 'ERROR:  duplicate key value violates unique constraint "events_uuid_key"')) {
                    if (1 === preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/', $e->getMessage(), $matches)) {
                        $uuid = $matches[0];
                        $uuid = mb_strtoupper($uuid);
                        foreach ($events as $event) {
                            if (Event\Metadata::fromObject($event)->get('uuid', '') === $uuid) {
                                throw new Exception\EventAlreadyInStore($event);
                            }
                        }
                    }
                }
            }

            throw $e;
        }

        while ($returned = $statement->fetch($this->pdo::FETCH_ASSOC)) {
            $sequence = $returned['number'];
            $uuid = $returned['uuid'];
            $uuid = mb_strtoupper($uuid);
            $version = $returned['producer_version'];

            foreach ($events as $event) {
                $metadata = Event\Metadata::fromObject($event);
                if ($uuid === $metadata->get('uuid')) {
                    $metadata->set('sequence', (string) $sequence);
                    $metadata->set('version', (string) $version);
                    $metadata->toObject($event);
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

    private function copy() : self
    {
        $stream = new self($this->pdo, $this->converter);
        $stream->from = $this->from;
        $stream->to = $this->to;
        $stream->after = $this->after;
        $stream->before = $this->before;
        $stream->limit = $this->limit;
        $stream->producers = $this->producers;

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
        $event = $row[self::EVENT_LOG_EVENT_BODY_COLUMN];
        $event = json_decode($event, true);
        $event = $this->converter->arrayToEvent($event);

        $metadata = $row[self::EVENT_LOG_EVENT_METADATA_COLUMN];
        $metadata = json_decode($metadata, true);
        $metadata = Event\Metadata::fromArray($metadata);
        $metadata->set('sequence', (string) $row[self::EVENT_LOG_SEQUENCE_COLUMN]);
        $metadata->set('version', (string) $row[self::EVENT_LOG_PRODUCER_VERSION_COLUMN]);

        $metadata->toObject($event);

        return $event;
    }

    private function toRow(Domain\Id $producerId, int $version, $event) : array
    {
        $metadata = Event\Metadata::fromObject($event);
        $metadata::clear($event);

        if (!$metadata->has('uuid')) {
            $metadata->set('uuid', UUID::create()->toString());
        }

        $row = [
            self::EVENT_LOG_EVENT_UUID_COLUMN => $metadata->get('uuid'),
            self::EVENT_LOG_EVENT_TYPE_COLUMN => get_class($event),
            self::EVENT_LOG_EVENT_BODY_COLUMN => json_encode($this->converter->eventToArray($event)),
            self::EVENT_LOG_EVENT_METADATA_COLUMN => json_encode($metadata->toArray()),
            self::EVENT_LOG_PRODUCER_TYPE_COLUMN => get_class($producerId),
            self::EVENT_LOG_PRODUCER_ID_COLUMN => $producerId->toString(),
            self::EVENT_LOG_PRODUCER_VERSION_COLUMN => $version,
        ];

        $metadata->toObject($event);

        return $row; // put metadata back
    }
}
