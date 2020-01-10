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
use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionCompleted;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionIgnoredEvent;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionListenedToEvent;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionListenersStateChanged;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionRestarted;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionStarted;
use Streak\Domain\Event\Sourced\Subscription\Stream as SubscriptionStream;
use Streak\Domain\Event\Subscription\Exception;
use Streak\Domain\EventStore;
use Streak\Domain\Versionable;
use Streak\Infrastructure\Event\InMemoryStream;
use Streak\Infrastructure\Event\Sourced\Subscription\InMemoryState;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
final class Subscription implements Event\Subscription, Event\Sourced, Versionable
{
    use Event\Sourcing {
        Event\Sourcing::replay as private doReplay;
    }

    private const LIMIT_TO_INITIAL_STREAM = 0;

    private $listener;
    private $lastState;
    private $clock;
    private $completedBy;
    private $startedBy;
    private $starting = false;
    private $lastProcessedEvent;

    public function __construct(Event\Listener $listener, Domain\Clock $clock)
    {
        $this->listener = $listener;
        $this->clock = $clock;
        $this->lastState = InMemoryState::empty();
    }

    /**
     * @return iterable|Event[]
     *
     * @throws Exception\SubscriptionAlreadyCompleted
     * @throws Exception\SubscriptionNotStartedYet
     * @throws \Throwable
     */
    public function subscribeTo(EventStore $store, ?int $limit = null) : iterable
    {
        if (null === $limit) {
            $limit = self::LIMIT_TO_INITIAL_STREAM; // if no $limit was given, we listen to initial stream only
        } elseif ($limit < 1) {
            throw new \InvalidArgumentException(sprintf('$limit must be a positive integer, but %d was given.', $limit));
        }

        if (false === $this->started()) {
            throw new Exception\SubscriptionNotStartedYet($this);
        }

        if (true === $this->completed()) {
            throw new Exception\SubscriptionAlreadyCompleted($this);
        }

        $stream = $store->stream(); // all events

        // we are not interested in events of other subscriptions
        $stream = $stream->without(SubscriptionStarted::class, SubscriptionListenedToEvent::class, SubscriptionIgnoredEvent::class, SubscriptionCompleted::class, SubscriptionRestarted::class);

        if ($this->listener instanceof Event\Filterer) {
            $stream = $this->listener->filter($stream);
        }

        if (true === $this->starting()) {
            // as we have just started (or restarted) this subscription let's begin listening from event that initiated subscription...
            $starter = $this->startedBy;

            if ($this->listener instanceof Event\Picker) {
                // ...or from one that listener picked for us
                $starter = $this->listener->pick($store);
            }

            $stream = $stream->from($starter);
        } else {
            // lets continue from event after last one we have listened to
            $stream = $stream->after($this->lastProcessedEvent);
        }

//        if ($limit) {
//            $stream = $stream->limit($limit); // TODO: optimize DbalPostgresEventStore::limit() implementation and enable it here
//        }

        $listened = 0;
        foreach ($stream as $event) {
            $this->listenToEvent($event);

            yield $event;

            $listened = $listened + 1;

            if ($this->completed()) {
                return;
            }

            if ($listened === $limit) { // we have exhausted $limit - if given - of events to listen to
                break;
            }
        }

        if (self::LIMIT_TO_INITIAL_STREAM === $limit) { // we have listened to initial stream and we stop now as instructed
            return;
        }

        // in the meantime of listening of $stream of events above new events might have been added to the event store - let's listen
        // to them too, until the $limit is exhausted.

        if (0 === $listened) { // if there were no events to listen right now, my bet is, there will be none next time too
            return;
        }

        $limit = $limit - $listened;

        if (0 === $limit) {
            return; // $limit exhausted
        }

        // not using "yield from" consciously as, in case of this method, every generator deeper into recursion yields
        // same keys. This behaviour, although correct, can screw up results in conjunction with iterator_to_array()
        // or any other function that uses keys yielded by generator.
        foreach ($this->subscribeTo($store, $limit) as $event) {
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

        if (true === $this->starting()) { // subscription is already starting, no need for another start
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

    /**
     * TODO: filter only newer event from $stream if replaying more than once.
     */
    public function replay(Event\Stream $stream) : void
    {
        /** @var $last Event\Sourced\Subscription\Event */
        $last = $stream->last();
        $stream = $stream->to($last); // freeze the stream, ignore any events that were stored after replaying process started

        // replay started, restarted or completed events
        $substream = $stream->only(SubscriptionStarted::class, SubscriptionRestarted::class, SubscriptionCompleted::class); // inclusion is faster

        $this->doReplay($substream);

        if ($this->completed()) {
            $this->lastReplayed = $last;
            $this->lastEvent = $last;
            $this->version = $last->subscriptionVersion();

            return;
        }

        // replaying last listened-to-event or ignored-event event
        $substream = $substream->after($substream->last());
        $substream = $substream->only(SubscriptionListenedToEvent::class, SubscriptionIgnoredEvent::class); // inclusion is faster
        $processed = $substream->last(); // TODO: $substream = $substream->reverse()->limit(1) would return last event already as a stream

        if ($processed) {
            $this->doReplay(new InMemoryStream($processed));
        }

        // replaying last listeners-state-changed event
        $substream = $substream->only(SubscriptionListenersStateChanged::class); // inclusion is faster
        $changed = $substream->last();

        if ($changed) {
            $this->doReplay(new InMemoryStream($changed));
        } else {
            // only if no state change was found
            if ($this->listener instanceof Event\Listener\Replayable) {
                $this->listener->replay(new SubscriptionStream($stream->only(SubscriptionListenedToEvent::class)));
            }
        }

        $this->lastReplayed = $last;
        $this->lastEvent = $last;
        $this->version = $last->subscriptionVersion(); // after event envelope will be introduced Subscription\Event::subscriptionVersion() can be removed
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

    public function starting() : bool
    {
        return $this->starting;
    }

    public function completed() : bool
    {
        return null !== $this->completedBy;
    }

    /**
     * @see applySubscriptionListenedToEvent
     * @see applySubscriptionIgnoredEvent
     * @see applySubscriptionListenersStateChanged
     * @see applySubscriptionCompleted
     */
    private function listenToEvent(Event $event) : void
    {
        if (true === $this->starting()) {
            // we are (re)starting subscription, lets reset listener if possible
            if ($this->listener instanceof Event\Listener\Resettable) {
                $this->listener->reset();
            }
        }

        $listened = $this->listener->on($event);

        if (true === $listened) {
            $this->applyEvent(new SubscriptionListenedToEvent($event, $this->nextExpectedVersion(), $this->clock->now()));
        } else {
            $this->applyEvent(new SubscriptionIgnoredEvent($event, $this->nextExpectedVersion(), $this->clock->now()));
        }

        if ($this->listener instanceof Listener\Stateful) {
            $currentState = $this->listener->toState(InMemoryState::empty());
            $currentState = InMemoryState::fromState($currentState);

            if (false === $this->lastState->equals($currentState)) {
                $this->applyEvent(new SubscriptionListenersStateChanged($currentState, $this->nextExpectedVersion(), $this->clock->now()));
            }
        }

        if ($this->listener instanceof Event\Listener\Completable) {
            if ($this->listener->completed()) {
                $this->applyEvent(new SubscriptionCompleted($this->nextExpectedVersion(), $this->clock->now()));
            }
        }
    }

    private function applySubscriptionListenedToEvent(SubscriptionListenedToEvent $event)
    {
        $this->starting = false;
        $this->lastProcessedEvent = $event->event();
    }

    private function applySubscriptionIgnoredEvent(SubscriptionIgnoredEvent $event)
    {
        $this->starting = false;
        $this->lastProcessedEvent = $event->event();
    }

    private function applySubscriptionCompleted(SubscriptionCompleted $event)
    {
        $this->completedBy = $this->lastEvent;
        $this->starting = false;
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

    private function applySubscriptionListenersStateChanged(SubscriptionListenersStateChanged $event)
    {
        $this->lastState = $event->state();

        if ($this->listener instanceof Listener\Stateful) {
            $this->listener->fromState($event->state());
        }
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
