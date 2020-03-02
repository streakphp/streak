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

namespace Streak\Infrastructure\Event\Subscription\DAO;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Types\Type;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Subscription;
use Streak\Domain\Exception\ObjectNotSupported;
use Streak\Infrastructure\Event\Sourced\Subscription\InMemoryState;
use Streak\Infrastructure\Event\Subscription\DAO;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class DbalPostgresDAO implements DAO
{
    /**
     * @var Subscription\Factory
     */
    private $subscriptions;

    /**
     * @var Event\Listener\Factory
     */
    private $listeners;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Event\Converter
     */
    private $converter;

    public function __construct(Subscription\Factory $subscriptions, Event\Listener\Factory $listeners, Connection $connection, Event\Converter $converter)
    {
        $this->subscriptions = $subscriptions;
        $this->listeners = $listeners;
        $this->connection = $connection;
        $this->converter = $converter;
    }

    public function save(Subscription $subscription) : void
    {
        try {
            $this->doSave($subscription);
        } catch (TableNotFoundException $e) {
            $this->create();

            $this->doSave($subscription);
        }
    }

    public function one(Event\Listener\Id $id) : ?Subscription
    {
        try {
            return $this->doOne($id);
        } catch (TableNotFoundException $e) {
            return null;
        }
    }

    public function exists(Listener\Id $id) : bool
    {
        try {
            return $this->doExists($id);
        } catch (TableNotFoundException $e) {
            return false;
        }
    }

    /**
     * @param string[] $types
     *
     * @return Subscription[]
     */
    public function all(array $types = [], ?bool $completed = null) : iterable
    {
        try {
            yield from $this->doAll($types, $completed);
        } catch (TableNotFoundException $e) {
            yield from [];
        }
    }

    public function toRow(DAO\Subscription $subscription) : array
    {
        // dehydrate
        $row['subscription_type'] = get_class($subscription->subscriptionId());
        $row['subscription_id'] = $subscription->subscriptionId()->toString();
        $row['subscription_version'] = $subscription->version();

        $reflection = new \ReflectionObject($subscription);

        $property = $reflection->getProperty('state');
        $property->setAccessible(true);
        $row['state'] = $property->getValue($subscription);
        $row['state'] = $row['state']->toArray();
        $row['state'] = json_encode($row['state']);
        $property->setAccessible(false);

        $property = $reflection->getProperty('startedBy');
        $property->setAccessible(true);
        $row['started_by'] = $property->getValue($subscription);
        if (null !== $row['started_by']) {
            $row['started_by'] = $this->converter->objectToArray($row['started_by']);
            $row['started_by'] = json_encode($row['started_by']);
        }
        $property->setAccessible(false);

        $property = $reflection->getProperty('startedAt');
        $property->setAccessible(true);
        $row['started_at'] = $property->getValue($subscription);
        if (null !== $row['started_at']) {
            $row['started_at'] = $this->toTimestamp($row['started_at']);
        }
        $property->setAccessible(false);

        $property = $reflection->getProperty('lastProcessedEvent');
        $property->setAccessible(true);
        $row['last_processed_event'] = $property->getValue($subscription);
        if (null !== $row['last_processed_event']) {
            $row['last_processed_event'] = $this->converter->objectToArray($row['last_processed_event']);
            $row['last_processed_event'] = json_encode($row['last_processed_event']);
        }
        $property->setAccessible(false);

        $property = $reflection->getProperty('lastEventProcessedAt');
        $property->setAccessible(true);
        $row['last_event_processed_at'] = $property->getValue($subscription);
        if (null !== $row['last_event_processed_at']) {
            $row['last_event_processed_at'] = $this->toTimestamp($row['last_event_processed_at']);
        }
        $property->setAccessible(false);

        $property = $reflection->getProperty('completed');
        $property->setAccessible(true);
        $row['completed'] = $property->getValue($subscription);
        $row['completed'] = $this->connection->convertToDatabaseValue($row['completed'], Type::BOOLEAN);
        $property->setAccessible(false);

        return $row;
    }

    public function create()
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS subscriptions
(
  id SERIAL PRIMARY KEY,
  subscription_type VARCHAR(255) NOT NULL,
  subscription_id VARCHAR(52) NOT NULL,
  subscription_version INT NOT NULL,
  state JSONB DEFAULT NULL,
  started_by JSONB DEFAULT NULL,
  started_at TIMESTAMP(6) WITH TIME ZONE DEFAULT NULL, -- microsecond precision
  last_processed_event JSONB DEFAULT NULL,
  last_event_processed_at TIMESTAMP(6) WITH TIME ZONE DEFAULT NULL, -- microsecond precision
  completed BOOLEAN NOT NULL DEFAULT FALSE,
  UNIQUE(subscription_type, subscription_id)
);
SQL;
        $statement = $this->connection->prepare($sql);
        $statement->execute();
    }

    public function drop()
    {
        $sql = 'DROP TABLE IF EXISTS subscriptions;';

        $statement = $this->connection->prepare($sql);
        $statement->execute();
    }

    private function fromRow($row) : Subscription
    {
        $id = $row['subscription_type'];
        $id = $id::fromString($row['subscription_id']);

        $row['state'] = json_decode($row['state'], true);
        $row['state'] = InMemoryState::fromArray($row['state']);

        if (null !== $row['started_by']) {
            $row['started_by'] = json_decode($row['started_by'], true);
            $row['started_by'] = $this->converter->arrayToObject($row['started_by']);
        }
        if (null !== $row['started_at']) {
            $row['started_at'] = $this->fromTimestamp($row['started_at']);
        }

        if (null !== $row['last_processed_event']) {
            $row['last_processed_event'] = json_decode($row['last_processed_event'], true);
            $row['last_processed_event'] = $this->converter->arrayToObject($row['last_processed_event']);
        }

        if (null !== $row['last_event_processed_at']) {
            $row['last_event_processed_at'] = $this->fromTimestamp($row['last_event_processed_at']);
        }

        $row['completed'] = $this->connection->convertToPHPValue($row['completed'], Type::BOOLEAN);

        $listener = $this->listeners->create($id);
        $subscription = $this->subscriptions->create($listener);

        $unwrapped = $this->unwrap($subscription);

        // hydrate
        $reflection = new \ReflectionObject($unwrapped);

        $property = $reflection->getProperty('version');
        $property->setAccessible(true);
        $property->setValue($unwrapped, $row['subscription_version']);
        $property->setAccessible(false);

        $property = $reflection->getProperty('state');
        $property->setAccessible(true);
        $property->setValue($unwrapped, $row['state']);
        $property->setAccessible(false);

        $property = $reflection->getProperty('startedBy');
        $property->setAccessible(true);
        $property->setValue($unwrapped, $row['started_by']);
        $property->setAccessible(false);

        $property = $reflection->getProperty('startedAt');
        $property->setAccessible(true);
        $property->setValue($unwrapped, $row['started_at']);
        $property->setAccessible(false);

        $property = $reflection->getProperty('lastProcessedEvent');
        $property->setAccessible(true);
        $property->setValue($unwrapped, $row['last_processed_event']);
        $property->setAccessible(false);

        $property = $reflection->getProperty('lastEventProcessedAt');
        $property->setAccessible(true);
        $property->setValue($unwrapped, $row['last_event_processed_at']);
        $property->setAccessible(false);

        $property = $reflection->getProperty('completed');
        $property->setAccessible(true);
        $property->setValue($unwrapped, $row['completed']);
        $property->setAccessible(false);

        return $subscription;
    }

    private function toTimestamp(\DateTimeInterface $when) : string
    {
        $when = $when->format('Y-m-d H:i:s.u P');

        return $when;
    }

    private function fromTimestamp(string $when) : \DateTimeImmutable
    {
        $timestamp = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u P', $when);

        // TIL that PDO is rounding microseconds when retrieving TIMESTAMP fields from postgresql and if those microseconds happened to be "000000"
        // then they are removed entirely e.g. "2020-01-22 19:48:42.000000+00" becomes "2020-01-22 19:48:42+00".
        // It can affect other DBSes and precisions.
        if (false === $timestamp) {
            $timestamp = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s P', $when); // @ignoreCodeCoverage
        }

        return $timestamp;
    }

    /**
     * @param \Streak\Infrastructure\Event\Subscription\DAO\Subscription $subscription
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function doSave(DAO\Subscription $subscription) : void
    {
        $sql = <<<SQL
INSERT INTO subscriptions (subscription_type, subscription_id, subscription_version, state, started_by, started_at, last_processed_event, last_event_processed_at, completed) 
VALUES (:subscription_type, :subscription_id, :subscription_version, :state, :started_by, :started_at, :last_processed_event, :last_event_processed_at, :completed)
ON CONFLICT ON CONSTRAINT subscriptions_subscription_type_subscription_id_key
DO UPDATE SET subscription_version = :subscription_version, state = :state, last_processed_event = :last_processed_event, last_event_processed_at = :last_event_processed_at, completed = :completed
SQL;

        $row = $this->toRow($subscription);

        $statement = $this->connection->prepare($sql);
        $statement->execute($row);
    }

    /**
     * @param Listener\Id $id
     *
     * @return \Streak\Infrastructure\Event\Subscription\DAO\Subscription|null
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function doOne(Event\Listener\Id $id)
    {
        $sql = 'SELECT subscription_type, subscription_id, subscription_version, state, started_by, started_at, last_processed_event, last_event_processed_at, completed FROM subscriptions WHERE subscription_type = :subscription_type AND subscription_id = :subscription_id LIMIT 1';

        $statement = $this->connection->prepare($sql);
        $statement->execute([
            'subscription_type' => get_class($id),
            'subscription_id' => $id->toString(),
        ]);

        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        $statement->closeCursor();

        if (false === $row) {
            return null;
        }

        $subscription = $this->fromRow($row);

        return $subscription;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function doExists(Listener\Id $id) : bool
    {
        $sql = 'SELECT subscription_type, subscription_id FROM subscriptions WHERE subscription_type = :subscription_type AND subscription_id = :subscription_id LIMIT 1';

        $statement = $this->connection->prepare($sql);
        $statement->execute([
            'subscription_type' => get_class($id),
            'subscription_id' => $id->toString(),
        ]);

        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        $statement->closeCursor();

        if (false === $row) {
            return false;
        }

        return true;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function doAll(array $types, ?bool $completed) : \Generator
    {
        $sql = 'SELECT subscription_type, subscription_id, subscription_version, state, started_by, started_at, last_processed_event, last_event_processed_at, completed FROM subscriptions';
        $where = [];
        $parameters = [];

        if ($types) {
            $sub = [];
            foreach ($types as $key => $type) {
                $sub[] = " (subscription_type = :subscription_type_$key) ";
                $parameters["subscription_type_$key"] = $type;
            }
            $where[] = '('.implode(' OR ', $sub).')';
        }

        if (true === $completed) {
            $where[] = ' completed IS TRUE ';
        }
        if (false === $completed) {
            $where[] = ' completed IS FALSE ';
        }

        $where = implode(' AND ', $where);

        if ($where) {
            $sql = "$sql WHERE $where ";
        }

        $sql .= ' ORDER BY id';

        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);

        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $subscription = $this->fromRow($row);

            yield $subscription;
        }

        $statement->closeCursor();
    }

    private function unwrap(Subscription $subscription) : DAO\Subscription
    {
        $exception = new ObjectNotSupported($subscription);

        if ($subscription instanceof DAO\Subscription) {
            return $subscription;
        }

        // @codeCoverageIgnoreStart
        while ($subscription instanceof Subscription\Decorator) {
            $subscription = $subscription->subscription();

            if ($subscription instanceof DAO\Subscription) {
                return $subscription;
            }
        }

        throw $exception;
        // @codeCoverageIgnoreEnd
    }
}
