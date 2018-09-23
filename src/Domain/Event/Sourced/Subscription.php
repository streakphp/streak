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
 */
final class Subscription implements Event\Subscription, Event\Sourced, Event\Process, Versionable
{
    use Event\Sourcing {
        Event\Sourcing::replay as private doReplay;
    }

    private $listener;
    private $clock;
    private $completedBy;
    private $startedFor;
    private $starting = false;

    /**
     * @TODO: subscription requires system clock
     */
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

        $stream = $store->stream();

        // process represents transaction that has beginning (hence filtering stream) and end.
        if ($this->listener instanceof Event\Process) {
            // TODO: It should be more explicit:
            //  - make listener able to configure which event should be its starting event
            //  - make subscription be configurable via (maybe) different kind of subscribers
            //
            // Problem with ideas above is "what should we do with stateful/replayable listeners that are started with event #100 and then
            // points to event #1 as starting event? Should be event #100 used to initialize state of the listener and feed it with stream from event #1?".
            //
            // An example might be process manager for syncing user account balances with currency exchange.
            // Lets assume that every exchange has its own process manager (which holds exchange id for reference) and such process manager is started by exchange-created event.
            // We have to feed exchange with account-credited & account-debited events from before this aforementioned exchange-created event, but if we do that how does process manager know its exchange id?

            if ($last instanceof SubscriptionStarted) {
                // lets start from event that brought up this subscription
                $stream = $stream->from($last->startFrom());
            }

            if ($last instanceof SubscriptionRestarted) {
                // lets start over
                $stream = $stream->from($last->restartFrom());
            }
        }

        if ($last instanceof SubscriptionListenedToEvent) {
            // lets continue from next event after last one we have listened too
            $stream = $stream->after($last->event());
        }

        if ($last instanceof SubscriptionIgnoredEvent) {
            // lets continue from next event after last one we have ignored
            $stream = $stream->after($last->event());
        }

        // we are not interested in other subscriptions events
        $stream = $stream->without(SubscriptionStarted::class, SubscriptionListenedToEvent::class, SubscriptionIgnoredEvent::class, SubscriptionCompleted::class, SubscriptionRestarted::class);

        foreach ($stream as $event) {
            try {
                $this->applyEvent(new SubscriptionListenedToEvent($event, $this->nextExpectedVersion(), $this->clock->now()));
            } catch (Exception\EventNotProcessed $exception) {
                $this->applyEvent(new SubscriptionIgnoredEvent($exception->event()->event(), $this->nextExpectedVersion(), $this->clock->now()));
            }

            if ($this->listener instanceof Event\Process) {
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

        if (!$this->listener instanceof Domain\Resettable) {
            throw new Exception\SubscriptionRestartNotPossible($this);
        }

        if (true === $this->starting) { // subscription is already starting, no need for restart
            return;
        }

        $this->applyEvent(new SubscriptionRestarted($this->startedFor, $this->nextExpectedVersion(), $this->clock->now()));
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
        $stream = $stream->without(SubscriptionIgnoredEvent::class);

        try {
            $backup = $this->listener;
            $this->listener = NullListener::from($this->listener);
            $this->doReplay($stream);
            $this->lastEvent = $last;
            $this->version = $last->subscriptionVersion();
        } finally {
            $this->listener = $backup;
        }

        if ($this->listener instanceof Event\Replayable) {
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

    public function subscriptionId() : Domain\Id
    {
        return $this->listener->id();
    }

    public function started() : bool
    {
        return null !== $this->startedFor;
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
            if ($this->listener instanceof Domain\Resettable) {
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
        $this->startedFor = $event->startFrom();
        $this->starting = true;
    }

    private function applySubscriptionRestarted(SubscriptionRestarted $event)
    {
        $this->startedFor = $event->restartFrom();
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
