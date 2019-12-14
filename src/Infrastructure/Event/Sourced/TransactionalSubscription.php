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

namespace Streak\Infrastructure\Event\Sourced;

use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Event\Subscription;
use Streak\Domain\EventStore;
use Streak\Domain\Versionable;
use Streak\Infrastructure\Event\Sourced as EventSourced;
use Streak\Infrastructure\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class TransactionalSubscription implements Subscription, Event\Sourced, Versionable
{
    private $subscription;
    private $uow;

    public function __construct(EventSourced\Subscription $subscription, UnitOfWork $uow)
    {
        $this->subscription = $subscription;
        $this->uow = $uow;
    }

    public function subscriptionId() : Event\Listener\Id
    {
        return $this->subscription->subscriptionId();
    }

    public function listener() : Event\Listener
    {
        return $this->subscription->listener();
    }

    public function subscribeTo(EventStore $store, int $limit) : iterable
    {
        try {
            $this->uow->add($this);
            foreach ($this->subscription->subscribeTo($store, $limit) as $event) {
                iterator_to_array($this->uow->commit());
                $this->uow->add($this);

                yield $event;
            }
            iterator_to_array($this->uow->commit());
            $this->uow->add($this);
        } catch (\Throwable $exception) {
            $this->uow->clear();

            throw $exception;
        }
    }

    public function startFor(Event $event) : void
    {
        try {
            $this->uow->add($this);
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
            $this->uow->add($this);
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

    public function equals($object) : bool
    {
        if ($object instanceof self) {
            $object = $object->subscription;
        }

        return $this->subscription->equals($object);
    }

    public function lastReplayed() : ?Event
    {
        return $this->subscription->lastReplayed();
    }

    public function producerId() : Domain\Id
    {
        return $this->subscription->producerId();
    }

    public function events() : array
    {
        return $this->subscription->events();
    }

    public function replay(Event\Stream $stream) : void
    {
        $this->subscription->replay($stream);
    }

    public function version() : int
    {
        return $this->subscription->version();
    }

    public function commit() : void
    {
        $this->subscription->commit();
    }
}
