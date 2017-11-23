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
use Streak\Domain\AggregateRoot;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class InMemoryEventStore implements Domain\EventStore
{
    private $events = [];

    public function addEvents(AggregateRoot $aggregate, Event ...$events) : void
    {
        if (!isset($this->events[$aggregate->id()->toString()])) {
            $this->events[$aggregate->id()->toString()] = [];
        }

        $this->events[$aggregate->id()->toString()] = array_merge($this->events[$aggregate->id()->toString()], $events);
    }

    public function getEvents(AggregateRoot $aggregate) : array
    {
        if (!isset($this->events[$aggregate->id()->toString()])) {
            $this->events[$aggregate->id()->toString()] = [];
        }

        return $this->events[$aggregate->id()->toString()];
    }

    public function clear()
    {
        $this->events = [];
    }
}
