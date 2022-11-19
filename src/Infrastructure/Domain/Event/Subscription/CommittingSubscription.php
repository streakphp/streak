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

use Streak\Domain\Event;
use Streak\Domain\Event\Subscription;
use Streak\Domain\EventStore;
use Streak\Infrastructure\Domain\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Domain\Event\Subscription\CommittingSubscriptionTest
 */
class CommittingSubscription implements Subscription, Subscription\Decorator
{
    public function __construct(private Subscription $subscription, private UnitOfWork $uow)
    {
    }

    public function subscription(): Subscription
    {
        return $this->subscription;
    }

    public function id(): Event\Listener\Id
    {
        return $this->subscription->id();
    }

    public function listener(): Event\Listener
    {
        return $this->subscription->listener();
    }

    public function subscribeTo(EventStore $store, ?int $limit = null): iterable
    {
        try {
            $this->uow->add($this->subscription);
            foreach ($this->subscription->subscribeTo($store, $limit) as $event) {
                iterator_to_array($this->uow->commit());
                $this->uow->add($this->subscription);

                yield $event;
            }
            iterator_to_array($this->uow->commit());
            $this->uow->add($this->subscription);
        } catch (\Throwable $exception) {
            $this->uow->clear();

            throw $exception;
        }
    }

    public function startFor(Event\Envelope $event): void
    {
        try {
            $this->uow->add($this->subscription);
            $this->subscription->startFor($event);
            iterator_to_array($this->uow->commit());
        } catch (\Throwable $exception) {
            $this->uow->clear();

            throw $exception;
        }
    }

    public function restart(): void
    {
        try {
            $this->uow->add($this->subscription);
            $this->subscription->restart();
            iterator_to_array($this->uow->commit());
        } catch (\Throwable $exception) {
            $this->uow->clear();

            throw $exception;
        }
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
        try {
            $this->uow->add($this->subscription);
            $this->subscription->pause();
            iterator_to_array($this->uow->commit());
        } catch (\Throwable $exception) {
            $this->uow->clear();

            throw $exception;
        }
    }

    public function unpause(): void
    {
        try {
            $this->uow->add($this->subscription);
            $this->subscription->unpause();
            iterator_to_array($this->uow->commit());
        } catch (\Throwable $exception) {
            $this->uow->clear();

            throw $exception;
        }
    }

    public function version(): int
    {
        return $this->subscription->version();
    }
}
