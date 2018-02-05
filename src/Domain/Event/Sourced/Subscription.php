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

namespace Streak\Domain\Event\Sourced;

use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionCompleted;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionListenedToEvent;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionStarted;
use Streak\Domain\EventStore;
use Streak\Infrastructure\Event\NullListener;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
final class Subscription implements Event\Subscription, Event\Sourced, Event\Completable
{
    use Event\Sourcing {
        Event\Sourcing::applyEvent as private doApplyEvent;
        Event\Sourcing::replay as private doReplay;
    }

    private $listener;
    private $completed = false;
    private $started = false;

    public function __construct(Event\Listener $listener)
    {
        $this->listener = $listener; // TODO: $this->listener = new LockableListener($listener);
    }

    public function start(\DateTimeInterface $startedAt)
    {
        $this->applyEvent(new SubscriptionStarted($startedAt));
    }

    public function subscribeTo(EventStore $store, $limit = 1) // TODO: put in ReadySubscription::process($limit = 1)
    {
        $stream = $store->stream();

        if ($this->lastReplayed()) {
            $stream = $stream->after($this->lastReplayed());
        }
        $stream = $stream->limit($limit);

        foreach ($stream as $event) {
            $this->applyEvent(new SubscriptionListenedToEvent($event));

            if ($this->listener instanceof Event\Completable) {
                if ($this->listener->completed()) {
                    $this->applyEvent(new SubscriptionCompleted());
                    break; // we stop here
                }
            }
        }
    }

    public function replay(Event\Stream $stream) : void
    {
        try {
            $backup = $this->listener; // TODO: $this->listener->lock();
            $this->listener = NullListener::from($this->listener);
            $this->doReplay($stream);
        } finally {
            $this->listener = $backup;  // TODO: $this->listener->unlock();
        }

        if ($this->listener instanceof Event\Replayable) { // TODO: $this->listener->decorates() instanceof Event\Replayable
            $unpacked = new class($stream) extends \FilterIterator implements Event\Stream { // extract into RuntimeFilteringStream class
                private $stream;
                private $position = 0;

                public function __construct(Event\Stream $stream)
                {
                    parent::__construct($stream);

                    $this->stream = $stream;
                }

                public function accept()
                {
                    $event = $this->getInnerIterator()->current();

                    if ($event instanceof SubscriptionListenedToEvent) {
                        return true;
                    }

                    return false;
                }

                public function next()
                {
                    parent::next();
                    ++$this->position;
                }

                public function key()
                {
                    parent::key();

                    return $this->position;
                }

                public function first() : ?Event
                {
                    $event = $this->stream->first();

                    if ($event instanceof SubscriptionListenedToEvent) {
                        return $event->event();
                    }

                    return $event;
                }

                public function last() : ?Event
                {
                    $event = $this->stream->last();

                    if ($event instanceof SubscriptionListenedToEvent) {
                        return $event->event();
                    }

                    return $event;
                }

                public function empty() : bool
                {
                    return $this->stream->empty();
                }

                public function current() : Event
                {
                    $event = $this->getInnerIterator()->current();

                    return $event->event();
                }
            };

            $this->listener->replay($unpacked);
        }
    }

    public function applyStart(SubscriptionStarted $event)
    {
        $this->started = true;
    }

    public function applyEventHandled(SubscriptionListenedToEvent $event)
    {
        if (false === $this->started) {
            throw new \BadMethodCallException();
        }

        $this->listener->on($event->event());
    }

    public function applyCompleted(SubscriptionCompleted $event)
    {
        $this->completed = true;
    }

    public function equals($object) : bool
    {
        if (!$object instanceof self) {
            return false;
        }

        if (!$this->subscriptionId()->equals($object->subscriptionId())) {
            return false;
        }

        return true;
    }

    public function producerId() : Domain\Id
    {
        return $this->subscriptionId();
    }

    public function subscriptionId() : Domain\Id
    {
        return $this->listener->id();
    }

    public function completed() : bool
    {
        return $this->completed;
    }
}
