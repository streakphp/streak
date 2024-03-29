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

namespace Streak\Domain\AggregateRoot;

use Streak\Domain;
use Streak\Domain\Aggregate;
use Streak\Domain\AggregateRoot;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Domain\EventSourcingTest
 */
trait EventSourcing //implements Event\Sourced\AggregateRoot
{
    use Aggregate\EventSourcing;

    /**
     * @var Event\Envelope[]
     */
    private array $events = [];
    private ?Event\Envelope $lastEvent = null;
    private int $version = 0;

    abstract public function id(): AggregateRoot\Id;

    /**
     * @throws \Throwable
     */
    final public function replay(Event\Stream $stream): void
    {
        foreach ($stream as $event) {
            $this->applyEvent($event);

            $this->version = $event->version();
        }

        $this->events = [];
    }

    final public function lastEvent(): ?Event\Envelope
    {
        return $this->lastEvent;
    }

    final public function version(): int
    {
        return $this->version;
    }

    /**
     * @return Event\Envelope[]
     */
    final public function events(): array
    {
        return $this->events;
    }

    public function commit(): void
    {
        $this->version += \count($this->events);
        $this->events = [];
    }

    /**
     * @throws Event\Exception\TooManyEventApplyingMethodsFound
     * @throws Domain\Exception\EventMismatched
     * @throws \Throwable
     */
    final public function applyEvent(Event\Envelope $event): void
    {
        // $event was produced by $this aggregate or one of its embedded entities
        if (!$this->id()->equals($event->producerId())) {
            throw new Domain\Exception\EventMismatched($this, $event);
        }

        if (null === $event->version()) {
            $event = $event->defineVersion($this->version + \count($this->events) + 1);
        }

        if ($this->id()->equals($event->entityId())) {
            $this->doApplyEvent($event);

            $this->lastEvent = $event;
            $this->events[] = $event;

            return;
        }

        $applied = 0;
        foreach ($this->eventSourcedEntities() as $entity) {
            /** @var Event\Sourced\Entity $aggregate */
            if ($entity->id()->equals($event->entityId())) {
                do {
                    try {
                        $entity->applyEvent($event);
                        $applied++;
                    } catch (Event\Exception\NoEventApplyingMethodFound) {
                    }
                } while ($entity = $entity->aggregate());

                break; // we don't need to look for next entity
            }
        }

        if ($applied === 0) {
            throw new Event\Exception\NoEventApplyingMethodFound($this, $event);
        }

        try {
            $this->doApplyEvent($event);
        } catch (Event\Exception\NoEventApplyingMethodFound) {
        }

        $this->lastEvent = $event;
        $this->events[] = $event;
    }

    /**
     * @throws Event\Exception\NoEventApplyingMethodFound
     * @throws Event\Exception\TooManyEventApplyingMethodsFound
     * @throws \Throwable
     */
    private function apply(Event $event): void
    {
        $envelope = Event\Envelope::new($event, $this->id());

        $this->applyEvent($envelope);
    }

    /**
     * @return Event\Sourced\Entity[]
     */
    private function eventSourcedEntities(): iterable
    {
        yield from Event\Sourced\Entity\Helper::for($this)->extractEventSourcedEntities();
    }
}
