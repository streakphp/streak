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

namespace Streak\Infrastructure\Domain\EventStore;

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
 *
 * @see \Streak\Infrastructure\Domain\EventStore\DbalPostgresEventStoreTest
 */
class DbalPostgresEventStore implements \Iterator, EventStore, Event\Stream, Schemable, Schema
{
    private const EVENT_METADATA_NUMBER = 'number';

    private const POSTGRES_PLATFORM_NAME = 'postgresql';

    private const DIRECTION_FORWARD = 'forward';
    private const DIRECTION_BACKWARD = 'backward';

    private ?array $current = null;
    private int $key = 0;

    private ?Statement $statement = null;

    private EventStore\Filter $filter;
    private array $only = [];
    private array $without = [];
    private ?Event\Envelope $from = null;
    private ?Event\Envelope $to = null;
    private ?Event\Envelope $after = null;
    private ?Event\Envelope $before = null;
    private ?int $limit = null;

    /**
     * @var Event\Envelope[]
     */
    private array $session = [];

    public function __construct(private Connection $connection, private Event\Converter $converter)
    {
        $this->filter = new EventStore\Filter();
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \RuntimeException
     */
    public function checkPlatform(): void
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
    public function create(): void
    {
        $this->checkPlatform();
        $this->connection->beginTransaction();

        $sqls[] = <<<'SQL'
            CREATE TABLE IF NOT EXISTS events (
              number BIGSERIAL,
              uuid UUID NOT NULL,
              type VARCHAR(256) NOT NULL,
              body JSONB NOT NULL,
              metadata JSONB NOT NULL,
              producer_type VARCHAR(256) NOT NULL,
              producer_id VARCHAR(256) NOT NULL,
              producer_version INT,
              appended_at timestamp NOT NULL DEFAULT clock_timestamp(),
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
    public function drop(): void
    {
        $this->checkPlatform();
        $this->connection->beginTransaction();

        $sqls[] = 'DROP TABLE IF EXISTS events';
        $sqls[] = 'DROP TABLE IF EXISTS subscriptions';

        foreach ($sqls as $sql) {
            $statement = $this->connection->prepare($sql);
            $statement->execute();
        }

        $this->connection->commit();
    }

    public function schema(): ?Schema
    {
        return $this;
    }

    public function add(Event\Envelope ...$events): array
    {
        if (0 === \count($events)) {
            return [];
        }

        $sql = 'INSERT INTO events (uuid, type, body, metadata, producer_type, producer_id, producer_version) ';

        $parameters = [];
        $values = [];
        foreach ($events as $key => $event) {
            $row = $this->toRow($event);

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

        $sql = "{$sql} VALUES {$values}";
        $sql = "{$sql} RETURNING number, uuid";

        try {
            $this->connection->beginTransaction();
            $this->connection->exec('LOCK TABLE events IN SHARE UPDATE EXCLUSIVE MODE;');
            $statement = $this->connection->prepare($sql);
            $statement->execute($parameters);
            $this->connection->commit();
        } catch (UniqueConstraintViolationException $e) {
            if ($id = $this->extractIdForConcurrentWrite($e)) {
                $this->connection->rollBack();

                throw new Exception\ConcurrentWriteDetected($id); // maybe supplement version as well?
            }
            if ($uuid = $this->extractIdForEventAlreadyInStore($e)) {
                foreach ($events as $event) {
                    if ($event->uuid()->equals($uuid)) {
                        $this->connection->rollBack();

                        throw new Exception\EventAlreadyInStore($event);
                    }
                }
            }
            $this->connection->rollBack(); // @codeCoverageIgnore

            throw $e; // @codeCoverageIgnore
        } catch (\Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        }

        while ($returned = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $number = (string) $returned['number'];
            $uuid = new UUID($returned['uuid']);

            foreach ($events as &$event) {
                if ($event->uuid()->equals($uuid)) {
                    $event = $event->set(self::EVENT_METADATA_NUMBER, $number);

                    $this->session[$event->uuid()->toString()] = $event;

                    continue;
                }
            }
        }

        return $events;
    }

    public function from(Event\Envelope $event): Event\Stream
    {
        $stream = $this->copy();
        $stream->from = $event;
        $stream->after = null;

        return $stream;
    }

    public function to(Event\Envelope $event): Event\Stream
    {
        $stream = $this->copy();
        $stream->to = $event;
        $stream->before = null;

        return $stream;
    }

    public function after(Event\Envelope $event): Event\Stream
    {
        $stream = $this->copy();
        $stream->from = null;
        $stream->after = $event;

        return $stream;
    }

    public function before(Event\Envelope $event): Event\Stream
    {
        $stream = $this->copy();
        $stream->to = null;
        $stream->before = $event;

        return $stream;
    }

    public function stream(?EventStore\Filter $filter = null): Event\Stream
    {
        $stream = $this->copy();
        if (null !== $filter) {
            $stream->filter = $filter;
        }

        return $stream;
    }

    public function only(string ...$types): Event\Stream
    {
        $stream = $this->copy();
        $stream->only = $types;
        $stream->without = [];

        // TODO: check if type is Domain\Id

        return $stream;
    }

    public function without(string ...$types): Event\Stream
    {
        $stream = $this->copy();
        $stream->without = $types;
        $stream->only = [];

        // TODO: check if type is Domain\Id

        return $stream;
    }

    public function limit(int $limit): Stream
    {
        $count = $this->count();

        if ($limit > $count) {
            $limit = $count;
        }

        $stream = $this->copy();
        $stream->limit = $limit;

        return $stream;
    }

    public function first(): ?Event\Envelope
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

        return $this->fromRow($row);
    }

    public function last(): ?Event\Envelope
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

        return $this->fromRow($row);
    }

    public function empty(): bool
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

    public function current(): Event\Envelope
    {
        return $this->fromRow($this->current);
    }

    public function next(): void
    {
        $row = $this->statement->fetch(\PDO::FETCH_ASSOC);

        if (false === $row) {
            $row = null;
        }

        $this->current = $row;
        $this->key = $this->key + 1;
    }

    public function key()
    {
        return $this->key;
    }

    public function valid()
    {
        return null !== $this->current;
    }

    public function rewind(): void
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

        $row = $this->statement->fetch(\PDO::FETCH_ASSOC);

        if (false === $row) {
            $row = null;
        }

        $this->current = $row;
        $this->key = 0;
    }

    public function event(UUID $uuid): ?Event\Envelope
    {
        $sql = 'SELECT * FROM events WHERE uuid = :uuid';

        $statement = $this->connection->prepare($sql);
        $statement->execute(['uuid' => $uuid->toString()]);

        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if (false === $row) {
            return null;
        }

        return $this->fromRow($row);
    }

    private function toId($class, $id): Domain\Id
    {
        $reflection = new \ReflectionClass($class);

        // @codeCoverageIgnoreStart
        if (!$reflection->implementsInterface(Domain\Id::class)) {
            throw new \InvalidArgumentException(); // TODO: domain exception here
        }
        // @codeCoverageIgnoreEnd

        $method = $reflection->getMethod('fromString');

        return $method->invoke(null, $id);
    }

    private function count(): int
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

    private function copy(): self
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
        $stream->session = $this->session;

        return $stream;
    }

    private function select(
        EventStore\Filter $filter,
        ?string $direction,
        ?array $columns,
        ?Event\Envelope $from,
        ?Event\Envelope $to,
        ?Event\Envelope $after,
        ?Event\Envelope $before,
        ?array $only,
        ?array $without,
        ?int $limit,
        ?int $offset
    ): Statement {
        $columns = implode(' , ', $columns);

        if (!$columns) {
            $columns = '*';
        }

        $sql = "SELECT {$columns} FROM events ";
        $where = [];
        $parameters = [];

        if (\count($filter->producerIds()) > 0) {
            // @var $filter Domain\Id[]
            $sub = [];
            foreach ($filter->producerIds() as $key => $id) {
                $sub[] = " (producer_type = :producer_type_{$key} AND producer_id = :producer_id_{$key}) ";
                $parameters["producer_type_{$key}"] = $id::class;
                $parameters["producer_id_{$key}"] = $id->toString();
            }
            $where[] = '('.implode(' OR ', $sub).')';
        }

        if (\count($filter->producerTypes()) > 0) {
            // @var $filter Domain\Id[]
            $sub = [];
            foreach ($filter->producerTypes() as $key => $type) {
                $sub[] = " (producer_type = :only_producer_type_{$key}) ";
                $parameters["only_producer_type_{$key}"] = $type;
            }
            $where[] = '('.implode(' OR ', $sub).')';
        }

        if ($only) {
            // @var $only string[]
            $sub = [];
            foreach ($only as $key => $type) {
                $sub[] = " (type = :include_type_{$key}) ";
                $parameters["include_type_{$key}"] = $type;
            }
            $where[] = '('.implode(' OR ', $sub).')';
        }

        if ($without) {
            // @var $without string[]
            $sub = [];
            foreach ($without as $key => $type) {
                $sub[] = " (type != :exclude_type_{$key}) ";
                $parameters["exclude_type_{$key}"] = $type;
            }
            $where[] = '('.implode(' AND ', $sub).')';
        }

        if ($from) {
            if (isset($this->session[$from->uuid()->toString()])) {
                $from = $this->session[$from->uuid()->toString()];
            }

            $where[] = ' (number >= :from) ';
            $parameters['from'] = $from->get(self::EVENT_METADATA_NUMBER);
        }

        if ($to) {
            if (isset($this->session[$to->uuid()->toString()])) {
                $to = $this->session[$to->uuid()->toString()];
            }

            $where[] = ' (number <= :to) ';
            $parameters['to'] = $to->get(self::EVENT_METADATA_NUMBER);
        }

        if ($after) {
            if (isset($this->session[$after->uuid()->toString()])) {
                $after = $this->session[$after->uuid()->toString()];
            }

            $where[] = ' (number > :after) ';
            $parameters['after'] = $after->get(self::EVENT_METADATA_NUMBER);
        }

        if ($before) {
            if (isset($this->session[$before->uuid()->toString()])) {
                $before = $this->session[$before->uuid()->toString()];
            }

            $where[] = ' (number < :before) ';
            $parameters['before'] = $before->get(self::EVENT_METADATA_NUMBER);
        }

        $where = implode(' AND ', $where);

        if ($where) {
            $sql = "{$sql} WHERE {$where} ";
        }

        if (self::DIRECTION_FORWARD === $direction) {
            $sql .= ' ORDER BY number ASC ';
        }

        if (self::DIRECTION_BACKWARD === $direction) {
            $sql .= ' ORDER BY number DESC ';
        }

        if ($offset) {
            if ($offset >= 0) {
                $sql .= ' OFFSET :offset ';
                $parameters['offset'] = $offset;
            }
        }

        if ($limit) {
            $sql .= ' LIMIT :limit ';
            $parameters['limit'] = $limit;
        }

        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);

        return $statement;
    }

    private function fromRow(array $row): Event\Envelope
    {
        $uuid = new UUID($row['uuid']);

        $body = $row['body'];
        $body = json_decode($body, true);
        $body = $this->converter->arrayToObject($body);

        /* TODO: store entity type and id as columns */
        $metadata = $row['metadata'];
        $metadata = json_decode($metadata, true);

        $producerId = $this->toId($row['producer_type'], $row['producer_id']);
        $entityId = $this->toId($metadata['entity_type'], $metadata['entity_id']);

        $event = new Event\Envelope(
            $uuid,
            $row['type'],
            $body,
            $producerId,
            $row['producer_version'],
        );

        $metadata[self::EVENT_METADATA_NUMBER] = $row['number'];

        foreach ($metadata as $name => $value) {
            $event = $event->set($name, $value);
        }

        $this->session[$event->uuid()->toString()] = $event;

        return $event;
    }

    private function toRow(Event\Envelope $event): array
    {
        return [
            'uuid' => $event->uuid()->toString(),
            'type' => $event->name(),
            'body' => json_encode($this->converter->objectToArray($event->message())),
            'metadata' => json_encode($event->metadata()),
            'producer_type' => $event->producerId()::class,
            'producer_id' => $event->producerId()->toString(),
            'producer_version' => $event->version(),
        ];
    }

    private function extractIdForConcurrentWrite(UniqueConstraintViolationException $e)
    {
        // example:
        // SQLSTATE[23505]: Unique violation: 7 ERROR:  duplicate key value violates unique constraint "events_producer_type_producer_id_producer_version_key"
        // DETAIL:  Key (producer_type, producer_id, producer_version)=(Streak\Infrastructure\Domain\EventStore\EventStoreTestCase\ProducerId1, producer1, 1) already exists.
        $error = $e->getMessage();

        // make sure this error is all about our concurrency detection unique index... check for constraint name only, as whole message may be localised
        if (false === mb_strpos($error, '"events_producer_type_producer_id_producer_version_key"')) {
            return null;
        }

        // extract producer type and id from error message
        preg_match('/\\(producer_type, producer_id, producer_version\\)=\\((?P<type>[\\S]+), (?P<id>[\\S]+), (?P<version>[0-9]+)\\)/', $error, $matches);

        if (false === isset($matches['type'])) {
            return null; // @codeCoverageIgnore
        }

        if (false === isset($matches['id'])) {
            return null; // @codeCoverageIgnore
        }

        return $this->toId($matches['type'], $matches['id']);
    }

    private function extractIdForEventAlreadyInStore(UniqueConstraintViolationException $e)
    {
        // example:
        // SQLSTATE[23505]: Unique violation: 7 ERROR:  duplicate key value violates unique constraint "events_uuid_key"
        // DETAIL:  Key (uuid)=(ec6c9774-0a0a-4155-926c-314cd2d5cc1f) already exists.
        $error = $e->getMessage();

        // make sure this error is all about our concurrency detection unique index... check for constraint name only, as whole message may be localised
        if (false === mb_strpos($error, '"events_uuid_key"')) {
            return null; // @codeCoverageIgnore
        }

        // extract producer type and id from error message
        preg_match('/\\(uuid\\)=\\((?P<uuid>[\\S]+)\\)/', $error, $matches);

        if (false === isset($matches['uuid'])) {
            return null; // @codeCoverageIgnore
        }

        return new UUID($matches['uuid']);
    }
}
