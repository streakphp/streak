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

namespace Streak\Infrastructure\Domain\Event\Sourced;

use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Listener\State;
use Streak\Domain\Event\Subscription\Exception;
use Streak\Domain\EventStore;
use Streak\Infrastructure\Domain\Event\InMemoryStream;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionCompleted;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionIgnoredEvent;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionListenedToEvent;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionListenersStateChanged;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionPaused;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionRestarted;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionStarted;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionUnPaused;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\InMemoryState;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Domain\Event\Sourced\SubscriptionTest
 */
final class Subscription implements Event\Sourced\Subscription
{
    private const LIMIT_TO_INITIAL_STREAM = 0;
    private State  $lastState;
    private ?Event\Envelope $completedBy = null;
    private $startedBy;
    private bool $paused = false;
    private bool $starting = false;
    private $lastProcessedEvent;

    /**
     * @var Event\Envelope[]
     */
    private array $events = [];
    private ?Event\Envelope $lastEvent = null;
    private bool $replaying = false;
    private int $version = 0;

    public function __construct(private Event\Listener $listener, private Domain\Clock $clock)
    {
        $this->lastState = InMemoryState::empty();
    }

    public function id(): Listener\Id
    {
        return $this->listener->id();
    }

    /**
     * @throws Exception\SubscriptionAlreadyCompleted
     * @throws Exception\SubscriptionNotStartedYet
     * @throws \Throwable
     *
     * @return Event\Envelope[]|iterable
     */
    public function subscribeTo(EventStore $store, ?int $limit = null): iterable
    {
        if (null === $limit) {
            $limit = self::LIMIT_TO_INITIAL_STREAM; // if no $limit was given, we listen to initial stream only
        } elseif ($limit < 1) {
            throw new \InvalidArgumentException(\sprintf('$limit must be a positive integer, but %d was given.', $limit));
        }

        if (false === $this->started()) {
            throw new Exception\SubscriptionNotStartedYet($this);
        }

        if (true === $this->completed()) {
            throw new Exception\SubscriptionAlreadyCompleted($this);
        }

        if (true === $this->paused()) {
            throw new Exception\SubscriptionPaused($this);
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

            $listened += 1;

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

        $limit -= $listened;

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
    public function restart(): void
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

        $this->apply(new SubscriptionRestarted($this->startedBy, $this->clock->now()));
    }

    public function pause(): void
    {
        if (false === $this->started()) {
            return;
        }

        if (true === $this->completed()) {
            return;
        }

        if (true === $this->paused()) {
            return;
        }

        $this->apply(new SubscriptionPaused($this->clock->now()));
    }

    public function unpause(): void
    {
        if (false === $this->started()) {
            return;
        }

        if (true === $this->completed()) {
            return;
        }

        if (false === $this->paused()) {
            return;
        }

        $this->apply(new SubscriptionUnPaused($this->clock->now()));
    }

    /**
     * @see applySubscriptionStarted
     *
     * @throws \Throwable
     */
    public function startFor(Event\Envelope $event): void
    {
        if (true === $this->started()) {
            throw new Exception\SubscriptionAlreadyStarted($this);
        }

        $this->apply(new SubscriptionStarted($event, $this->clock->now()));
    }

    /**
     * TODO: filter only newer event from $stream if replaying more than once.
     */
    public function replay(Event\Stream $stream): void
    {
        $last = $stream->last();

        if (null === $last) {
            return;
        }

        $stream = $stream->to($last); // freeze the stream, ignore any events that were stored after replaying process started

        // replay started, restarted or completed events
        $substream = $stream->only(SubscriptionStarted::class, SubscriptionRestarted::class, SubscriptionCompleted::class); // inclusion is faster

        $this->doReplay($substream);

        if ($this->completed()) {
            $this->lastEvent = $last;
            $this->version = (int) $last->version();

            return;
        }

        // replaying last listened-to-event, ignored-event, paused-event or unpaused-event
        $substream = $substream->after($substream->last());
        $substream = $substream->only(SubscriptionListenedToEvent::class, SubscriptionIgnoredEvent::class, SubscriptionPaused::class, SubscriptionUnPaused::class); // inclusion is faster
        $processed = $substream->last(); // TODO: $substream = $substream->reverse()->limit(1) would return last event already as a stream

        if ($processed) {
            $this->doReplay(new InMemoryStream($processed));
        }

        // replaying last listeners-state-changed event
        $substream = $substream->only(SubscriptionListenersStateChanged::class); // inclusion is faster
        $changed = $substream->last();

        if ($changed) {
            $this->doReplay(new InMemoryStream($changed));
        }

        $this->lastEvent = $last;
        $this->version = (int) $last->version();
    }

    public function equals(object $object): bool
    {
        if (!$object instanceof self) {
            return false;
        }

        if (!$this->id()->equals($object->id())) {
            return false;
        }

        return true;
    }

    public function listener(): Listener
    {
        return $this->listener;
    }

    public function started(): bool
    {
        return null !== $this->startedBy;
    }

    public function paused(): bool
    {
        return $this->paused;
    }

    public function starting(): bool
    {
        return $this->starting;
    }

    public function completed(): bool
    {
        return null !== $this->completedBy;
    }

    public function lastEvent(): ?Event\Envelope
    {
        return $this->lastEvent;
    }

    public function version(): int
    {
        return $this->version;
    }

    /**
     * @return Event\Envelope[]
     */
    public function events(): array
    {
        return $this->events;
    }

    public function commit(): void
    {
        $this->version += \count($this->events);
        $this->events = [];
    }

    /**
     * @throws \Throwable
     */
    private function doReplay(Event\Stream $stream): void
    {
        try {
            $this->replaying = true;
            foreach ($stream as $event) {
                $this->applyEvent($event);
            }
        } finally {
            $this->replaying = false;
        }
    }

    /**
     * @see applySubscriptionListenedToEvent
     * @see applySubscriptionIgnoredEvent
     * @see applySubscriptionListenersStateChanged
     * @see applySubscriptionCompleted
     */
    private function listenToEvent(Event\Envelope $event): void
    {
        if (true === $this->starting()) {
            // we are (re)starting subscription, lets reset listener if possible
            if ($this->listener instanceof Event\Listener\Resettable) {
                $this->listener->reset();
            }
        }

        $listened = $this->listener->on($event);

        if (true === $listened) {
            $this->apply(new SubscriptionListenedToEvent($event, $this->clock->now()));
        } else {
            $this->apply(new SubscriptionIgnoredEvent($event, $this->clock->now()));
        }

        if ($this->listener instanceof Listener\Stateful) {
            $currentState = $this->listener->toState(InMemoryState::empty());
            $currentState = InMemoryState::fromState($currentState);

            if (false === $this->lastState->equals($currentState)) {
                $this->apply(new SubscriptionListenersStateChanged($currentState, $this->clock->now()));
            }
        }

        if ($this->listener instanceof Event\Listener\Completable) {
            if ($this->listener->completed()) {
                $this->apply(new SubscriptionCompleted($this->clock->now()));
            }
        }
    }

    private function doApplyEvent(Event\Envelope $event): void
    {
        if ($event->message() instanceof SubscriptionListenedToEvent) {
            $this->applySubscriptionListenedToEvent($event);
        }
        if ($event->message() instanceof SubscriptionIgnoredEvent) {
            $this->applySubscriptionIgnoredEvent($event);
        }
        if ($event->message() instanceof SubscriptionCompleted) {
            $this->applySubscriptionCompleted();
        }
        if ($event->message() instanceof SubscriptionStarted) {
            $this->applySubscriptionStarted($event);
        }
        if ($event->message() instanceof SubscriptionRestarted) {
            $this->applySubscriptionRestarted($event);
        }
        if ($event->message() instanceof SubscriptionListenersStateChanged) {
            $this->applySubscriptionListenersStateChanged($event);
        }
        if ($event->message() instanceof SubscriptionPaused) {
            $this->applySubscriptionPaused($event);
        }
        if ($event->message() instanceof SubscriptionUnPaused) {
            $this->applySubscriptionUnPaused($event);
        }
    }

    private function applySubscriptionListenedToEvent(Event\Envelope $event): void
    {
        $this->starting = false;
        $this->lastProcessedEvent = $event->message()->event();
    }

    private function applySubscriptionIgnoredEvent(Event\Envelope $event): void
    {
        $this->starting = false;
        $this->lastProcessedEvent = $event->message()->event();
    }

    private function applySubscriptionCompleted(): void
    {
        $this->completedBy = $this->lastEvent;
        $this->starting = false;
        $this->paused = false;
    }

    private function applySubscriptionStarted(Event\Envelope $event): void
    {
        $this->startedBy = $event->message()->startedBy();
        $this->starting = true;
    }

    private function applySubscriptionPaused(Event\Envelope $event): void
    {
        $this->paused = true;
    }

    private function applySubscriptionUnPaused(Event\Envelope $event): void
    {
        $this->paused = false;
    }

    private function applySubscriptionRestarted(Event\Envelope $event): void
    {
        $this->startedBy = $event->message()->originallyStartedBy();
        $this->completedBy = null;
        $this->starting = true;
        $this->paused = false;
    }

    private function applySubscriptionListenersStateChanged(Event\Envelope $event): void
    {
        $state = $event->message()->state();
        $this->lastState = $state;

        if ($this->listener instanceof Listener\Stateful) {
            $this->listener->fromState($state);
        }
    }

    /**
     * @throws Event\Exception\NoEventApplyingMethodFound
     * @throws Event\Exception\TooManyEventApplyingMethodsFound
     * @throws \Throwable
     */
    private function apply(Event $event): void
    {
        $event = Event\Envelope::new(
            $event,
            $this->id(),
            $this->version + \count($this->events) + 1
        );

        $this->applyEvent($event);
    }

    /**
     * @throws \Throwable
     */
    private function applyEvent(Event\Envelope $event): void
    {
        if (!$this->id()->equals($event->producerId())) {
            throw new Domain\Exception\EventMismatched($this, $event);
        }

        $this->doApplyEvent($event);

        $this->lastEvent = $event;

        if ($this->replaying) {
            $this->version = $event->version();
        } else {
            $this->events[] = $event;
        }
    }
}
