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

namespace Streak\Infrastructure\Event\Subscription;

use Streak\Domain\Event;
use Streak\Domain\Event\Subscription;
use Streak\Domain\EventStore;
use Streak\Infrastructure\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class CommittingSubscription implements Subscription, Subscription\Decorator
{
    private $subscription;
    private $uow;

    public function __construct(Subscription $subscription, UnitOfWork $uow)
    {
        $this->subscription = $subscription;
        $this->uow = $uow;
    }

    public function subscription() : Subscription
    {
        return $this->subscription;
    }

    public function subscriptionId() : Event\Listener\Id
    {
        return $this->subscription->subscriptionId();
    }

    public function listener() : Event\Listener
    {
        return $this->subscription->listener();
    }

    public function subscribeTo(EventStore $store, ?int $limit = null) : iterable
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

    public function startFor(Event\Envelope $event) : void
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

    public function restart() : void
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

    public function starting() : bool
    {
        return $this->subscription->starting();
    }

    public function started() : bool
    {
        return $this->subscription->started();
    }

    public function completed() : bool
    {
        return $this->subscription->completed();
    }
}
