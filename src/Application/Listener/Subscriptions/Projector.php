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

namespace Streak\Application\Listener\Subscriptions;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Streak\Application\Listener\Subscriptions\Projector\Query\ListSubscriptions;
use Streak\Application\Query;
use Streak\Application\QueryHandler;
use Streak\Domain\Clock;
use Streak\Domain\Event;
use Streak\Domain\Event\Sourced\Subscription;
use Streak\Domain\EventStore;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class Projector implements Event\Listener, Event\Listener\Resettable, Event\Picker, Event\Filterer, QueryHandler
{
    use Event\Listener\Identifying;
    use Event\Listener\Listening;
    use Event\Listener\Filtering {
        filter as private preFilter;
    }
    use Query\Handling;

    private $connection;

    private $clock;

    public function __construct(Projector\Id $id, Connection $connection, Clock $clock)
    {
        $this->identifyBy($id);
        $this->connection = $connection;
        $this->clock = $clock;
    }

    public function handleListSubscriptions(ListSubscriptions $query) : iterable
    {
        $parameters = [];
        $sql = $this->connection
            ->createQueryBuilder()
            ->from('subscriptions', 's')
            ->select('s.*')
        ;

        if ($query::TYPES_NONE !== $query->types()) {
            $or = $sql->expr()->orX();
            foreach ($query->types() as $key => $type) {
                $or->add('s.subscription_type = ?');
            }
            $sql->where($or);
            $parameters = array_merge($parameters, $query->types());
        }

        if ($query::COMPLETENESS_NONE !== $query->completed()) {
            $sql->andWhere('s.is_completed = ?');
            $parameters[] = (int) $query->completed();
        }

        $sql->setParameters($parameters);
        $sql->orderBy('s.last_sync_at', 'ASC');

        $statement = $sql->execute();
        $subscriptions = [];

        foreach ($statement->fetchAll() as &$subscription) {
            $subscription['subscription_version'] = $this->connection->convertToPHPValue($subscription['subscription_version'], Type::INTEGER);
            $subscription['last_event_at'] = $this->connection->convertToPHPValue($subscription['last_event_at'], Type::DATETIMETZ);
            $subscription['last_sync_at'] = $this->connection->convertToPHPValue($subscription['last_sync_at'], Type::DATETIMETZ);

            $subscriptions[] = $subscription;
        }

        return $subscriptions;
    }

    public function filter(Event\Stream $stream) : Event\Stream
    {
        $stream = $this->preFilter($stream);
        $stream = $stream->withoutEventsProducedBy($this->id()); // we dont track our own subscription events as it would start infinite loop

        return $stream;
    }

    public function onSubscriptionStarted(Subscription\Event\SubscriptionStarted $event)
    {
        // @TODO: use message wrapper around event
        $metadata = Event\Metadata::fromObject($event);

        $last = $this->connection->convertToDatabaseValue($event->timestamp(), Type::DATETIMETZ);
        $now = $this->connection->convertToDatabaseValue($this->clock->now(), Type::DATETIMETZ);

        $sql = <<<SQL
        INSERT INTO subscriptions (subscription_type, subscription_id, subscription_version, last_event_uuid, last_event_at, last_sync_at, is_completed) VALUES (:subscription_type, :subscription_id, :subscription_version, :last_event_uuid, :last_event_at, :last_sync_at, false);
SQL;
        $statement = $this->connection->prepare($sql);
        $statement->execute([
            'subscription_type' => $metadata->get('producer_type'),
            'subscription_id' => $metadata->get('producer_id'),
            'subscription_version' => $event->subscriptionVersion(),
            'last_event_uuid' => $metadata->get('uuid'),
            'last_event_at' => $last,
            'last_sync_at' => $now,
        ]);

        $sql = <<<SQL
        UPDATE subscriptions SET subscription_version = subscriptions.subscription_version + 1, last_event_uuid = :last_event_uuid, last_event_at = :last_event_at, last_sync_at = :last_sync_at WHERE subscription_type = :subscription_type AND subscription_id = :subscription_id;
SQL;

        $statement = $this->connection->prepare($sql);
        $statement->execute([
            'last_event_uuid' => $metadata->get('uuid'),
            'last_event_at' => $last,
            'subscription_type' => Projector\Id::class,
            'subscription_id' => Projector\Id::ID,
            'last_sync_at' => $now,
        ]);
    }

    public function onSubscriptionRestarted(Subscription\Event\SubscriptionRestarted $event)
    {
        // @TODO: use message wrapper around event
        $metadata = Event\Metadata::fromObject($event);

        $last = $this->connection->convertToDatabaseValue($event->timestamp(), Type::DATETIMETZ);
        $now = $this->connection->convertToDatabaseValue($this->clock->now(), Type::DATETIMETZ);

        $sql = <<<SQL
        UPDATE subscriptions SET subscription_version = :subscription_version, last_event_uuid = :last_event_uuid, last_event_at = :last_event_at, is_completed = false, last_sync_at = :last_sync_at WHERE subscription_type = :subscription_type AND subscription_id = :subscription_id;
SQL;

        $statement = $this->connection->prepare($sql);
        $statement->execute([
            'subscription_version' => $event->subscriptionVersion(),
            'last_event_uuid' => $metadata->get('uuid'),
            'last_event_at' => $last,
            'subscription_type' => $metadata->get('producer_type'),
            'subscription_id' => $metadata->get('producer_id'),
            'last_sync_at' => $now,
        ]);

        $sql = <<<SQL
        UPDATE subscriptions SET subscription_version = subscriptions.subscription_version + 1, last_event_uuid = :last_event_uuid, last_event_at = :last_event_at, last_sync_at = :last_sync_at WHERE subscription_type = :subscription_type AND subscription_id = :subscription_id;
SQL;

        $statement = $this->connection->prepare($sql);
        $statement->execute([
            'last_event_uuid' => $metadata->get('uuid'),
            'last_event_at' => $last,
            'subscription_type' => Projector\Id::class,
            'subscription_id' => Projector\Id::ID,
            'last_sync_at' => $now,
        ]);
    }

    public function onSubscriptionListenedToEvent(Subscription\Event\SubscriptionListenedToEvent $event)
    {
        $this->onProgress($event);
    }

    public function onSubscriptionIgnoredEvent(Subscription\Event\SubscriptionIgnoredEvent $event)
    {
        $this->onProgress($event);
    }

    public function onSubscriptionCompleted(Subscription\Event\SubscriptionCompleted $event)
    {
        // @TODO: use message wrapper around event
        $metadata = Event\Metadata::fromObject($event);

        $last = $this->connection->convertToDatabaseValue($event->timestamp(), Type::DATETIMETZ);
        $now = $this->connection->convertToDatabaseValue($this->clock->now(), Type::DATETIMETZ);

        $sql = <<<SQL
        UPDATE subscriptions SET subscription_version = subscriptions.subscription_version + 1, last_event_uuid = :last_event_uuid, last_event_at = :last_event_at, is_completed = true, last_sync_at = :last_sync_at WHERE subscription_type = :subscription_type AND subscription_id = :subscription_id;
SQL;

        $statement = $this->connection->prepare($sql);
        $statement->execute([
            'last_event_uuid' => $metadata->get('uuid'),
            'last_event_at' => $last,
            'subscription_type' => $metadata->get('producer_type'),
            'subscription_id' => $metadata->get('producer_id'),
            'last_sync_at' => $now,
        ]);

        $sql = <<<SQL
        UPDATE subscriptions SET subscription_version = subscriptions.subscription_version + 1, last_event_uuid = :last_event_uuid, last_event_at = :last_event_at, last_sync_at = :last_sync_at WHERE subscription_type = :subscription_type AND subscription_id = :subscription_id;
SQL;
        $statement = $this->connection->prepare($sql);
        $statement->execute([
            'last_event_uuid' => $metadata->get('uuid'),
            'last_event_at' => $last,
            'subscription_type' => Projector\Id::class,
            'subscription_id' => Projector\Id::ID,
            'last_sync_at' => $now,
        ]);
    }

    public function reset() : void
    {
        $this->connection->beginTransaction();
        $this->drop();
        $this->create();
        $this->connection->commit();
    }

    public function pick(EventStore $store) : Event\Envelope
    {
        return $store
            ->stream()
            ->withEventsOfType(Subscription\Event\SubscriptionStarted::class)
            ->first()
        ;
    }

    public static function correlate(Event\Envelope $event) : Projector\Id
    {
        if (!$event->message() instanceof Subscription\Event\SubscriptionStarted) {
            throw new Event\Exception\InvalidEventGiven($event);
        }

        return new Projector\Id();
    }

    protected function preEvent(Event $event) : void
    {
        $this->connection->beginTransaction();
    }

    protected function postEvent(Event $event) : void
    {
        $this->connection->commit();
    }

    protected function onException(\Throwable $exception) : void
    {
        $this->connection->rollBack();
    }

    private function create()
    {
        $now = $this->connection->convertToDatabaseValue($this->clock->now(), Type::DATETIMETZ);

        $sql = <<<SQL
        CREATE TABLE subscriptions (
            subscription_type VARCHAR(256) NOT NULL,
            subscription_id VARCHAR(256) NOT NULL,
            subscription_version INT NOT NULL,
            last_event_uuid UUID NOT NULL,
            last_event_at TIMESTAMP(6) WITH TIME ZONE NOT NULL,
            is_completed BOOLEAN NOt NULL,
            last_sync_at TIMESTAMP(6) WITH TIME ZONE NOT NULL,
            UNIQUE (subscription_type, subscription_id)
        );
SQL;
        $statement = $this->connection->prepare($sql);
        $statement->execute();

        $sql = <<<SQL
        INSERT INTO subscriptions (subscription_type, subscription_id, subscription_version, last_event_uuid, last_event_at, last_sync_at, is_completed) VALUES (:subscription_type, :subscription_id, :subscription_version, :last_event_uuid, :last_event_at, :last_sync_at, false);
SQL;
        $statement = $this->connection->prepare($sql);
        $statement->execute([
            'subscription_type' => Projector\Id::class,
            'subscription_id' => Projector\Id::ID,
            'subscription_version' => 1,
            'last_event_uuid' => '00000000-0000-0000-0000-000000000000',
            'last_event_at' => $this->connection->convertToDatabaseValue(new \DateTime('@0'), Type::DATETIMETZ),
            'last_sync_at' => $now,
        ]);
    }

    private function drop()
    {
        $sql = <<<SQL
        DROP TABLE IF EXISTS subscriptions;
SQL;

        $statement = $this->connection->prepare($sql);
        $statement->execute();
    }

    private function onProgress(Subscription\Event $event)
    {
        // @TODO: use message wrapper around event
        $metadata = Event\Metadata::fromObject($event);

        $last = $this->connection->convertToDatabaseValue($event->timestamp(), Type::DATETIMETZ);
        $now = $this->connection->convertToDatabaseValue($this->clock->now(), Type::DATETIMETZ);

        $sql = <<<SQL
        UPDATE subscriptions SET subscription_version = :subscription_version, last_event_uuid = :last_event_uuid, last_event_at = :last_event_at, last_sync_at = :last_sync_at WHERE subscription_type = :subscription_type AND subscription_id = :subscription_id;
SQL;

        $statement = $this->connection->prepare($sql);
        $statement->execute([
            'subscription_version' => $event->subscriptionVersion(),
            'last_event_uuid' => $metadata->get('uuid'),
            'last_event_at' => $last,
            'subscription_type' => $metadata->get('producer_type'),
            'subscription_id' => $metadata->get('producer_id'),
            'last_sync_at' => $now,
        ]);

        $sql = <<<SQL
        UPDATE subscriptions SET subscription_version = subscriptions.subscription_version + 1, last_event_uuid = :last_event_uuid, last_event_at = :last_event_at, last_sync_at = :last_sync_at WHERE subscription_type = :subscription_type AND subscription_id = :subscription_id;
SQL;
        $statement = $this->connection->prepare($sql);
        $statement->execute([
            'last_event_uuid' => $metadata->get('uuid'),
            'last_event_at' => $last,
            'subscription_type' => Projector\Id::class,
            'subscription_id' => Projector\Id::ID,
            'last_sync_at' => $now,
        ]);
    }
}
