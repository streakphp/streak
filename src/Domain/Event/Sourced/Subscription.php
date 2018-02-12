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
use Streak\Domain\Event\Sourced\Subscription\Stream as SubscriptionStream;
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

    public function start(\DateTimeInterface $startedAt, Event $event)
    {
        $this->applyEvent(new SubscriptionStarted($startedAt, $event));
    }

    /**
     * @return iterable|Event[]
     */
    public function subscribeTo(EventStore $store) : iterable
    {
        $stream = $store->stream();

        if ($this->lastReplayed()) {
            $stream = $stream->from($this->lastReplayed()->event());
        }

        if ($this->listener instanceof Event\Completable) {
            if ($this->listener->completed()) {
                return;
            }
        }

        foreach ($stream as $event) {
            if ($event instanceof SubscriptionStarted) {
                continue;
            }
            if ($event instanceof SubscriptionListenedToEvent) {
                continue;
            }
            if ($event instanceof SubscriptionCompleted) {
                continue;
            }
            $this->applyEvent(new SubscriptionListenedToEvent($event));

            if ($this->listener instanceof Event\Completable) {
                if ($this->listener->completed()) {
                    $this->applyEvent(new SubscriptionCompleted());
                    break; // we stop here
                }
            }

            yield $event;
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
            $unpacked = new SubscriptionStream($stream);
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
