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
use Streak\Domain\Versionable;
use Streak\Infrastructure\Event\NullListener;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
final class Subscription implements Event\Subscription, Event\Sourced, Event\Completable, Versionable
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
        $this->listener = $listener;
    }

    public function startFor(Domain\Event $event, \DateTimeInterface $at)
    {
        $this->applyEvent(new SubscriptionStarted($event, $at));
    }

    /**
     * @return iterable|Event[]
     */
    public function subscribeTo(EventStore $store) : iterable
    {
        if (true === $this->completed) {
            return;
        }

        $stream = $store->stream();

        if ($this->lastReplayed()) {
            $event = $this->lastReplayed();

            if ($event instanceof SubscriptionStarted) {
                $stream = $stream->from($event->event());
            }

            if ($event instanceof SubscriptionListenedToEvent) {
                $stream = $stream->after($event->event());
            }
        }

//        $stream->notBy($this->producerId());
//        $stream->notOf(Subscription\Event::class); // should infer all implementations (not interfaces/abstract classes) in descendants tree.

        foreach ($stream as $event) {
            if (false === $this->applyEvent(new SubscriptionListenedToEvent($event))) {
                $this->undo();
                continue;
            }

            yield $event;

            if ($this->listener instanceof Event\Completable) {
                if ($this->listener->completed()) {
                    $this->applyEvent(new SubscriptionCompleted());
                    break;
                }
            }
        }
    }

    public function replay(Event\Stream $stream) : void
    {
        try {
            $backup = $this->listener;
            $this->listener = NullListener::from($this->listener); // replay() changes only state of subscription. It is not running actual listeners.
            $this->doReplay($stream);
        } finally {
            $this->listener = $backup;
        }

        if ($this->listener instanceof Event\Replayable) {
            $unpacked = new SubscriptionStream($stream);
            $this->listener->replay($unpacked);
        }
    }

    public function applyStart(SubscriptionStarted $event)
    {
        $this->started = true;
    }

    public function applyEventHandled(SubscriptionListenedToEvent $event) : bool
    {
        if (false === $this->started) {
            throw new \BadMethodCallException();
        }

        return $this->listener->on($event->event());
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
