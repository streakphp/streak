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

namespace Streak\Infrastructure\Domain\Event\Subscription;

use Doctrine\DBAL\Driver\Connection;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Subscription;
use Streak\Domain\EventStore;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Domain\Event\Subscription\DbalTransactionalSubscriptionTest
 */
class DbalTransactionalSubscription implements Subscription, Subscription\Decorator
{
    public function __construct(private Subscription $subscription, private Connection $connection, private int $maxTransactionSize = 1)
    {
    }

    public function subscription(): Subscription
    {
        return $this->subscription;
    }

    public function listener(): Listener
    {
        return $this->subscription->listener();
    }

    public function id(): Listener\Id
    {
        return $this->subscription->id();
    }

    public function subscribeTo(EventStore $store, ?int $limit = null): iterable
    {
        $this->connection->beginTransaction();
        $transaction = [];

        try {
            foreach ($this->subscription->subscribeTo($store, $limit) as $event) {
                $transaction[] = $event;

                if (\count($transaction) === $this->maxTransactionSize) {
                    $this->connection->commit();
                    $this->connection->beginTransaction();
                    while ($event = array_shift($transaction)) {
                        yield $event;
                    }
                }
            }
            $this->connection->commit();
            if (\count($transaction) > 0) {
                while ($event = array_shift($transaction)) {
                    yield $event;
                }
            }
        } catch (\Throwable $exception) {
            $this->connection->rollBack();

            throw $exception;
        }
    }

    public function startFor(Event\Envelope $event): void
    {
        $this->subscription->startFor($event);
    }

    public function restart(): void
    {
        $this->subscription->restart();
    }

    public function starting(): bool
    {
        return $this->subscription->starting();
    }

    public function started(): bool
    {
        return $this->subscription->started();
    }

    public function completed(): bool
    {
        return $this->subscription->completed();
    }

    public function paused(): bool
    {
        return $this->subscription->paused();
    }

    public function pause(): void
    {
        $this->subscription->pause();
    }

    public function unpause(): void
    {
        $this->subscription->unpause();
    }

    public function version(): int
    {
        return $this->subscription->version();
    }
}
