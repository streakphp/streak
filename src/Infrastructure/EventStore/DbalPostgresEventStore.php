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

use Aura\SqlQuery\QueryFactory;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
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
    private const EVENT_ATTRIBUTE_NUMBER = '__event_store_number__';

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
    private $withEventsOfType = [];

    /**
     * @var Domain\Id[]
     */
    private $withEventsProducedBy = [];
    private $withoutEventsOfType = [];

    /**
     * @var Domain\Id[]
     */
    private $withoutEventsProducedBy = [];
    private $from;
    private $to;
    private $after;
    private $before;
    private $limit;

    /**
     * @var Event\Envelope[]
     */
    private $session = [];

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
  appended_at TIMESTAMP(6) WITH TIME ZONE NOT NULL DEFAULT NOW(),
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

    public function add(Event\Envelope ...$events) : void
    {
        if (0 === count($events)) {
            return;
        }

        $factory = new QueryFactory('pgsql');
        /* @var $insert \Aura\SqlQuery\Pgsql\Insert */
        $insert = $factory->newInsert();
        $insert->into('events');

        foreach ($events as $key => $event) {
            $row = $this->toRow($event);

            $insert->addRow($row);
        }

        $insert->returning(['number', 'uuid', 'metadata', 'producer_version', 'appended_at']);

        try {
            $statement = $this->connection->prepare($insert->getStatement());
            $statement->execute($insert->getBindValues());
        } catch (UniqueConstraintViolationException $e) {
            if ($id = $this->extractIdForConcurrentWrite($e)) {
                throw new Exception\ConcurrentWriteDetected($id); // maybe supplement version as well?
            }
            if ($uuid = $this->extractIdForEventAlreadyInStore($e)) {
                foreach ($events as $event) {
                    if ($event->uuid()->equals($uuid)) {
                        throw new Exception\EventAlreadyInStore($event);
                    }
                }
            }
            throw $e; // @codeCoverageIgnore
        }


        while ($returned = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $number = (string) $returned['number'];
            $uuid = new UUID($returned['uuid']);

            foreach ($events as &$event) {
                if ($event->uuid()->equals($uuid)) {
                    $event = $event->set(self::EVENT_ATTRIBUTE_NUMBER, $number);

                    $this->session[$event->uuid()->toString()] = $event;

                    continue;
                }
            }
        }
    }

    public function from(Event\Envelope $event) : Event\Stream
    {
        $stream = $this->copy();
        $stream->from = $event;
        $stream->after = null;

        return $stream;
    }

    public function to(Event\Envelope $event) : Event\Stream
    {
        $stream = $this->copy();
        $stream->to = $event;
        $stream->before = null;

        return $stream;
    }

    public function after(Event\Envelope $event) : Event\Stream
    {
        $stream = $this->copy();
        $stream->from = null;
        $stream->after = $event;

        return $stream;
    }

    public function before(Event\Envelope $event) : Event\Stream
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

    public function withEventsProducedBy(Domain\Id ...$ids) : Stream
    {
        $stream = $this->copy();
        $stream->withEventsProducedBy = $ids;
        $stream->withoutEventsProducedBy = [];

        return $stream;
    }

    public function withoutEventsProducedBy(Domain\Id ...$ids) : Stream
    {
        $stream = $this->copy();
        $stream->withEventsProducedBy = [];
        $stream->withoutEventsProducedBy = $ids;

        return $stream;
    }

    public function withEventsOfType(string ...$types) : Event\Stream
    {
        $stream = $this->copy();
        $stream->withEventsOfType = $types;
        $stream->withoutEventsOfType = [];

        // TODO: check if type is Domain\Id

        return $stream;
    }

    public function withoutEventsOfType(string ...$types) : Event\Stream
    {
        $stream = $this->copy();
        $stream->withoutEventsOfType = $types;
        $stream->withEventsOfType = [];

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

    public function first() : ?Event\Envelope
    {
        $statement = $this->select(
            $this->filter,
            self::DIRECTION_FORWARD,
            [],
            $this->from,
            $this->to,
            $this->after,
            $this->before,
            $this->withEventsOfType,
            $this->withoutEventsOfType,
            $this->withEventsProducedBy,
            $this->withoutEventsProducedBy,
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

    public function last() : ?Event\Envelope
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
            $this->withEventsOfType,
            $this->withoutEventsOfType,
            $this->withEventsProducedBy,
            $this->withoutEventsProducedBy,
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
            $this->withEventsOfType,
            $this->withoutEventsOfType,
            $this->withEventsProducedBy,
            $this->withoutEventsProducedBy,
            1,
            null
        );

        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if (false === $row) {
            return true;
        }

        return false;
    }

    public function current() : Event\Envelope
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
            $this->withEventsOfType,
            $this->withoutEventsOfType,
            $this->withEventsProducedBy,
            $this->withoutEventsProducedBy,
            $this->limit,
            null
        );
        $this->current = $this->statement->fetch(\PDO::FETCH_ASSOC);
        $this->key = 0;
    }

    public function producerId($class, $id) : Domain\Id
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
            $this->withEventsOfType,
            $this->withoutEventsOfType,
            $this->withEventsProducedBy,
            $this->withoutEventsProducedBy,
            null,
            null
        );

        $count = $statement->fetchColumn(0);

        return (int) $count;
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
        $stream->withEventsOfType = $this->withEventsOfType;
        $stream->withEventsProducedBy = $this->withEventsProducedBy;
        $stream->withoutEventsOfType = $this->withoutEventsOfType;
        $stream->withoutEventsProducedBy = $this->withoutEventsProducedBy;
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
        ?array $withEventsOfType,
        ?array $withoutEventsOfType,
        ?array $withEventsProducedBy,
        ?array $withoutEventsProducedBy,
        ?int $limit,
        ?int $offset
    ) : Statement {
        $select = $this->connection
            ->createQueryBuilder()
            ->select('*')
            ->from('events')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
        ;

        if ($columns) {
            $select->select($columns);
        }

        $expr = $select->expr();
        $where = $expr->andX();

        if (count($filter->producerIds()) > 0) {
            $or = $expr->orX();
            foreach ($filter->producerIds() as $key => $id) {
                $and = $expr->andX();
                $and->add($expr->comparison('producer_type', $expr::EQ, $expr->literal(get_class($id))));
                $and->add($expr->comparison('producer_id', $expr::EQ, $expr->literal($id->toString())));
                $or->add($and);
            }
            $where->add($or);
        }

        if (count($filter->producerTypes()) > 0) {
            $or = $expr->orX();
            foreach ($filter->producerTypes() as $key => $type) {
                $or->add($expr->comparison('producer_type', $expr::EQ, $expr->literal($type)));
            }
            $where->add($or);
        }

        if ($withEventsOfType) {
            /* @var $withEventsOfType string[] */
            $or = $expr->orX();
            foreach ($withEventsOfType as $key => $type) {
                $or->add($expr->comparison('type', $expr::EQ, $expr->literal($type)));
            }
            $where->add($or);
        }

        if ($withoutEventsOfType) {
            /* @var $withoutEventsOfType string[] */
            $and = $expr->andX();
            foreach ($withoutEventsOfType as $key => $type) {
                $and->add($expr->comparison('type', $expr::NEQ, $expr->literal($type)));
            }
            $where->add($and);
        }

        if ($from) {
            if (isset($this->session[$from->uuid()->toString()])) {
                $from = $this->session[$from->uuid()->toString()];
            }
            $where->add($expr->comparison('number', $expr::GTE, $expr->literal($from->get(self::EVENT_ATTRIBUTE_NUMBER))));
        }

        if ($to) {
            if (isset($this->session[$to->uuid()->toString()])) {
                $to = $this->session[$to->uuid()->toString()];
            }
            $where->add($expr->comparison('number', $expr::LTE, $expr->literal($to->get(self::EVENT_ATTRIBUTE_NUMBER))));
        }

        if ($after) {
            if (isset($this->session[$after->uuid()->toString()])) {
                $after = $this->session[$after->uuid()->toString()];
            }
            $where->add($expr->comparison('number', $expr::GT, $expr->literal($after->get(self::EVENT_ATTRIBUTE_NUMBER))));
        }

        if ($before) {
            if (isset($this->session[$before->uuid()->toString()])) {
                $before = $this->session[$before->uuid()->toString()];
            }
            $where->add($expr->comparison('number', $expr::LT, $expr->literal($before->get(self::EVENT_ATTRIBUTE_NUMBER))));
        }

        if ($where->count() > 0) {
            $select->where($where);
        }

        if (self::DIRECTION_FORWARD === $direction) {
            $select->orderBy('number', 'ASC');
        }

        if (self::DIRECTION_BACKWARD === $direction) {
            $select->orderBy('number', 'DESC');
        }

        $statement = $select->execute();

        return $statement;
    }

    private function fromRow(array $row) : Event\Envelope
    {
        $uuid = new UUID($row['uuid']);

        $body = $row['body'];
        $body = json_decode($body, true);
        $body = $this->converter->arrayToEvent($body);

        $producerId = $this->producerId($row['producer_type'], $row['producer_id']);

        $event = new Event\Envelope(
            $uuid,
            $row['type'],
            $body,
            $producerId,
            $row['producer_version']
        );

        $metadata = $row['metadata'];
        $metadata = json_decode($metadata, true);
        $metadata[self::EVENT_ATTRIBUTE_NUMBER] = $row['number'];

        foreach ($metadata as $name => $value) {
            $event = $event->set($name, $value);
        }

        $this->session[$event->uuid()->toString()] = $event;

        return $event;
    }

    private function toRow(Event\Envelope $event) : array
    {
        $row = [
            'uuid' => $event->uuid()->toString(),
            'type' => $event->name(),
            'body' => json_encode($this->converter->eventToArray($event->message())),
            'metadata' => json_encode($event->metadata()),
            'producer_type' => get_class($event->producerId()),
            'producer_id' => $event->producerId()->toString(),
            'producer_version' => $event->version(),
        ];

        return $row;
    }

    private function extractIdForConcurrentWrite(UniqueConstraintViolationException $e)
    {
        // example:
        // SQLSTATE[23505]: Unique violation: 7 ERROR:  duplicate key value violates unique constraint "events_producer_type_producer_id_producer_version_key"
        // DETAIL:  Key (producer_type, producer_id, producer_version)=(Streak\Infrastructure\EventStore\EventStoreTestCase\ProducerId1, producer1, 1) already exists.
        $error = $e->getMessage();

        // make sure this error is all about our concurrency detection unique index... check for constraint name only, as whole message may be localised
        if (false === mb_strpos($error, '"events_producer_type_producer_id_producer_version_key"')) {
            return null;
        }

        // extract producer type and id from error message
        preg_match("/\(producer_type, producer_id, producer_version\)=\((?P<type>[\S]+), (?P<id>[\S]+), (?P<version>[0-9]+)\)/", $error, $matches);

        if (false === isset($matches['type'])) {
            return null; // @codeCoverageIgnore
        }

        if (false === isset($matches['id'])) {
            return null; // @codeCoverageIgnore
        }

        return $this->producerId($matches['type'], $matches['id']);
    }

    private function extractIdForEventAlreadyInStore(UniqueConstraintViolationException $e) : UUID
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
        preg_match("/\(uuid\)=\((?P<uuid>[\S]+)\)/", $error, $matches);

        if (false === isset($matches['uuid'])) {
            return null; // @codeCoverageIgnore
        }

        return new UUID($matches['uuid']);
    }
}
