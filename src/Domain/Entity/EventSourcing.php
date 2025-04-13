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

namespace Streak\Domain\Entity;

use Streak\Domain\Entity;
use Streak\Domain\Event;
use Streak\Domain\Exception\EventMismatched;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Domain\EventSourcingTest
 */
trait EventSourcing //implements Event\Sourced\Entity
{
    private ?Event\Sourced\AggregateRoot $aggregateRoot = null;
    private ?Event\Sourced\Aggregate $aggregate = null;

    public function registerAggregateRoot(Event\Sourced\AggregateRoot $aggregateRoot): void
    {
        $this->aggregateRoot = $aggregateRoot;
    }

    public function registerAggregate(Event\Sourced\Aggregate $aggregate): void
    {
        if ($this->id()->equals($aggregate->id())) {
            throw new \BadMethodCallException('You can\'t register aggregate on itself.');
        }

        $this->aggregate = $aggregate;
        $this->registerAggregateRoot($aggregate->aggregateRoot());
    }

    public function aggregateRoot(): Event\Sourced\AggregateRoot
    {
        if (null === $this->aggregateRoot) {
            throw new \BadMethodCallException(\sprintf('Aggregate root no registered. Did you forget to run %s::registerAggregateRoot()?', static::class));
        }

        return $this->aggregateRoot;
    }

    public function aggregate(): ?Event\Sourced\Aggregate
    {
        return $this->aggregate;
    }

    abstract public function id(): Entity\Id;

    final public function applyEvent(Event\Envelope $event): void
    {
        if (false === $this->aggregateRoot()->id()->equals($event->producerId())) {
            throw new EventMismatched($this, $event);
        }

        $this->doApplyEvent($event);
    }

    /**
     * @throws Event\Exception\NoEventApplyingMethodFound
     * @throws Event\Exception\TooManyEventApplyingMethodsFound
     * @throws \Throwable
     */
    protected function apply(Event\EntityEvent $event): void
    {
        $event = Event\Envelope::new($event, $this->aggregateRoot()->id());

        if (!$this->id()->equals($event->entityId())) {
            throw new EventMismatched($this, $event);
        }

        $this->aggregateRoot()->applyEvent($event);
    }

    private function doApplyEvent(Event\Envelope $event): void
    {
        Event\Sourced\Entity\Helper::for($this)->applyEvent($event);
    }
}
