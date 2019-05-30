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
use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionCompleted;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionIgnoredEvent;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionListenedToEvent;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionRestarted;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionStarted;
use Streak\Domain\Event\Sourced\Subscription\Stream as SubscriptionStream;
use Streak\Domain\Event\Subscription\Exception;
use Streak\Domain\EventStore;
use Streak\Domain\Versionable;
use Streak\Infrastructure\Event\NullListener;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * TODO: move under Application?
 */
final class Subscription implements Event\Subscription, Event\Sourced, Versionable
{
    use Event\Sourcing {
        Event\Sourcing::replay as private doReplay;
    }

    private $listener;
    private $clock;
    private $completedBy;
    private $startedBy;
    private $starting = false;

    public function __construct(Event\Listener $listener, Domain\Clock $clock)
    {
        $this->listener = $listener;
        $this->clock = $clock;
    }

    /**
     * @see applySubscriptionListenedToEvent
     * @see applySubscriptionIgnoredEvent
     * @see applySubscriptionCompleted
     *
     * @param EventStore $store
     *
     * @return iterable|Event[]
     *
     * @throws Exception\SubscriptionAlreadyCompleted
     * @throws Exception\SubscriptionNotStartedYet
     * @throws \Throwable
     */
    public function subscribeTo(EventStore $store) : iterable
    {
        if (false === $this->started()) {
            throw new Exception\SubscriptionNotStartedYet($this);
        }

        $last = $this->lastEvent();

        if ($last instanceof SubscriptionCompleted) {
            throw new Exception\SubscriptionAlreadyCompleted($this);
        }

        $stream = $store->stream(); // all events

        // we are not interested in events of other subscriptions
        $stream = $stream->without(SubscriptionStarted::class, SubscriptionListenedToEvent::class, SubscriptionIgnoredEvent::class, SubscriptionCompleted::class, SubscriptionRestarted::class);

        if ($this->listener instanceof Event\Filterer) {
            $stream = $this->listener->filter($stream);
        }

        if ($last instanceof SubscriptionStarted) {
            $starter = $this->startedBy;

            if ($this->listener instanceof Event\Picker) {
                $starter = $this->listener->pick($store);
            }

            $stream = $stream->from($starter);
        }

        if ($last instanceof SubscriptionRestarted) {
            $starter = $this->startedBy;

            if ($this->listener instanceof Event\Picker) {
                $starter = $this->listener->pick($store);
            }

            $stream = $stream->from($starter);
        }

        if ($last instanceof SubscriptionListenedToEvent) {
            // lets continue from next event after last one we have listened to
            $stream = $stream->after($last->event());
        }

        if ($last instanceof SubscriptionIgnoredEvent) {
            // lets continue from next event after last one we have ignored
            $stream = $stream->after($last->event());
        }

        foreach ($stream as $event) {
            try {
                $this->applyEvent(new SubscriptionListenedToEvent($event, $this->nextExpectedVersion(), $this->clock->now()));
            } catch (Exception\EventNotProcessed $exception) {
                $this->applyEvent(new SubscriptionIgnoredEvent($exception->event()->event(), $this->nextExpectedVersion(), $this->clock->now()));
            }

            if ($this->listener instanceof Event\Listener\Completable) {
                if ($this->listener->completed()) {
                    $this->applyEvent(new SubscriptionCompleted($this->nextExpectedVersion(), $this->clock->now()));
                    yield $event;
                    break;
                }
            }

            yield $event;
        }
    }

    /**
     * @see applySubscriptionRestarted
     *
     * @throws Exception\SubscriptionNotStartedYet
     * @throws Exception\SubscriptionRestartNotPossible
     * @throws \Throwable
     */
    public function restart() : void
    {
        if (false === $this->started()) {
            throw new Exception\SubscriptionNotStartedYet($this);
        }

        if (!$this->listener instanceof Event\Listener\Resettable) {
            throw new Exception\SubscriptionRestartNotPossible($this);
        }

        if (true === $this->starting) { // subscription is already starting, no need for restart
            return;
        }

        $this->applyEvent(new SubscriptionRestarted($this->startedBy, $this->nextExpectedVersion(), $this->clock->now()));
    }

    /**
     * @see applySubscriptionStarted
     *
     * @param Event $event
     *
     * @throws \Throwable
     */
    public function startFor(Domain\Event $event) : void
    {
        if (true === $this->started()) {
            throw new Exception\SubscriptionAlreadyStarted($this);
        }

        $this->applyEvent(new SubscriptionStarted($event, $this->clock->now()));
    }

    public function replay(Event\Stream $stream) : void
    {
        /** @var $last Subscription\Event */
        $last = $stream->last();
        $stream = $stream->to($last);
        $stream = $stream->only(SubscriptionStarted::class, SubscriptionRestarted::class, SubscriptionListenedToEvent::class); // inclusion is faster

        try {
            $backup = $this->listener;
            $this->listener = NullListener::from($this->listener);
            $this->doReplay($stream);
            $this->lastEvent = $last;
            $this->version = $last->subscriptionVersion();
        } finally {
            $this->listener = $backup;
        }

        if ($this->listener instanceof Event\Listener\Replayable) {
            $unpacked = new SubscriptionStream($stream);
            $this->listener->replay($unpacked);
        }
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

    public function subscriptionId() : Event\Listener\Id
    {
        return $this->listener->listenerId();
    }

    public function listener() : Listener
    {
        return $this->listener;
    }

    public function started() : bool
    {
        return null !== $this->startedBy;
    }

    public function completed() : bool
    {
        return null !== $this->completedBy;
    }

    private function applySubscriptionListenedToEvent(SubscriptionListenedToEvent $event)
    {
        $original = $event->event();

        if (true === $this->starting) {
            // we are (re)starting subscription, lets reset listener if possible
            if ($this->listener instanceof Event\Listener\Resettable) {
                $this->listener->reset();
            }
        }

        $processed = $this->listener->on($original);

        if (false === $processed) {
            throw new Exception\EventNotProcessed($event);
        }

        $this->starting = false;
    }

    private function applySubscriptionIgnoredEvent(SubscriptionIgnoredEvent $event)
    {
        $this->starting = false;
    }

    private function applySubscriptionCompleted(SubscriptionCompleted $event)
    {
        $this->completedBy = $this->lastEvent;
    }

    private function applySubscriptionStarted(SubscriptionStarted $event)
    {
        $this->startedBy = $event->startedBy();
        $this->starting = true;
    }

    private function applySubscriptionRestarted(SubscriptionRestarted $event)
    {
        $this->startedBy = $event->originallyStartedBy();
        $this->completedBy = null;
        $this->starting = true;
    }

    private function expectedVersion() : int
    {
        return $this->version + count($this->events);
    }

    private function nextExpectedVersion() : int
    {
        return $this->expectedVersion() + 1;
    }
}
