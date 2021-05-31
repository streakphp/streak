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

namespace Streak\Infrastructure\Domain\Event\Subscription\DAO;

use Streak\Domain\Clock;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Subscription\Exception;
use Streak\Domain\EventStore;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\InMemoryState;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Domain\Event\Subscription\DAO\SubscriptionTest
 */
class Subscription implements Event\Subscription
{
    private const LIMIT_TO_INITIAL_STREAM = 0;

    private Event\Listener $listener;
    private Clock $clock;
    private InMemoryState $state;
    private ?Event\Envelope $startedBy = null;
    private ?\DateTimeImmutable $startedAt = null;
    private ?\DateTimeImmutable $pausedAt = null;
    private ?Event\Envelope $lastProcessedEvent = null;
    private ?\DateTimeImmutable $lastEventProcessedAt = null;
    private int $version = 0;
    private bool $completed = false;

    public function __construct(Event\Listener $listener, Clock $clock)
    {
        $this->listener = $listener;
        $this->clock = $clock;
        $this->state = InMemoryState::empty();
    }

    public function listener(): Listener
    {
        return $this->listener;
    }

    public function subscriptionId(): Listener\Id
    {
        return $this->listener->listenerId();
    }

    public function subscribeTo(EventStore $store, ?int $limit = null): iterable
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

        if (true === $this->paused()) {
            throw new Exception\SubscriptionPaused($this);
        }

        $stream = $store->stream(); // all events

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

    public function startFor(Event\Envelope $event): void
    {
        if (true === $this->started()) {
            throw new Exception\SubscriptionAlreadyStarted($this);
        }

        if ($this->listener instanceof Listener\Stateful) {
            $this->state = $this->listener->toState($this->state);
        }

        $this->startedBy = $event;
        $this->startedAt = $this->clock->now();
        ++$this->version;
    }

    public function restart(): void
    {
        if (false === $this->started()) {
            throw new Exception\SubscriptionNotStartedYet($this);
        }

        if (true === $this->starting()) { // subscription is already starting, no need for another start
            return;
        }

        $this->lastProcessedEvent = null;
        $this->lastEventProcessedAt = null;
        $this->completed = false;
        $this->pausedAt = null;
        ++$this->version;
    }

    public function paused(): bool
    {
        if (null === $this->pausedAt) {
            return false;
        }

        return true;
    }

    public function pause(): void
    {
        if (false === $this->started()) {
            return;
        }

        if (true === $this->completed()) {
            return;
        }

        if (true === $this->paused()) { // subscription is already paused, no need for another pause
            return;
        }

        $this->pausedAt = $this->clock->now();
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

        $this->pausedAt = null;
    }

    public function starting(): bool
    {
        if (null === $this->startedBy) {
            return false;
        }

        if (true === $this->completed()) {
            return false;
        }

        if (null === $this->lastProcessedEvent) {
            return true;
        }

        return false;
    }

    public function started(): bool
    {
        if (null === $this->startedBy) {
            return false;
        }

        return true;
    }

    public function completed(): bool
    {
        return true === $this->completed;
    }

    public function version(): int
    {
        return $this->version;
    }

    private function listenToEvent(Event\Envelope $event): void
    {
        if (true === $this->starting()) {
            // we are (re)starting subscription, lets reset listener if possible
            if ($this->listener instanceof Event\Listener\Resettable) {
                $this->listener->reset();
            }
        }

        if ($this->listener instanceof Listener\Stateful) {
            $this->listener->fromState($this->state);
        }

        $this->listener->on($event);

        if ($this->listener instanceof Listener\Stateful) {
            $this->state = $this->listener->toState(InMemoryState::empty());
            $this->state = InMemoryState::fromState($this->state);
        }

        if ($this->listener instanceof Event\Listener\Completable) {
            if ($this->listener->completed()) {
                $this->completed = true;
            }
        }

        $this->lastProcessedEvent = $event;
        $this->lastEventProcessedAt = $this->clock->now();
        ++$this->version;
    }
}
