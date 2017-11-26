<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Infrastructure\EventStore;

use Streak\Domain;
use Streak\Domain\Exception;
use Streak\Domain\AggregateRoot;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class InMemoryEventStore implements Domain\EventStore
{
    private $events = [];

    /**
     * @throws Exception\ConcurrentWriteDetected
     * @throws Exception\InvalidAggregateGiven
     */
    public function addEvents(AggregateRoot $aggregate, Event ...$events) : void
    {
        $this->check($aggregate);

        if (!isset($this->events[$aggregate->id()->toString()])) {
            $this->events[$aggregate->id()->toString()] = [];
        }

        $this->events[$aggregate->id()->toString()] = array_merge($this->events[$aggregate->id()->toString()], $events);
    }

    /**
     * @return Domain\Event[]
     *
     * @throws Exception\InvalidAggregateGiven
     */
    public function getEvents(AggregateRoot $aggregate) : array
    {
        $this->check($aggregate);

        if (!isset($this->events[$aggregate->id()->toString()])) {
            $this->events[$aggregate->id()->toString()] = [];
        }

        return $this->events[$aggregate->id()->toString()];
    }

    public function clear()
    {
        $this->events = [];
    }

    /**
     * @throws Exception\InvalidAggregateGiven
     */
    private function check(AggregateRoot $aggregate) : void
    {
        if ($aggregate->id()->toString() === '') {
            throw new Exception\InvalidAggregateGiven($aggregate);
        }
    }
}
