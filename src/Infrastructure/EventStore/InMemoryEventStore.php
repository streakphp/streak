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

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class InMemoryEventStore implements Domain\EventStore
{
    private $events = [];
    private $all = [];

    public function add(Domain\Event ...$events) : void
    {
        foreach ($events as $event) {

            $this->check($event->aggregateRootId());

            $id = $event->aggregateRootId()->toString();
            if (!isset($this->events[$id])) {
                $this->events[$id] = [];
            }

            $this->events[$id][] = $event;
            $this->all[] = $event;
        }
    }

    public function find(Domain\AggregateRootId $id) : array
    {
        $this->check($id);

        if (!isset($this->events[$id->toString()])) {
            return [];
        }

        return $this->events[$id->toString()];
    }

    /**
     * @return Domain\Event[]
     */
    public function all() : array
    {
        return $this->all;
    }

    public function clear()
    {
        $this->events = [];
        $this->all = [];
    }

    public function check(Domain\AggregateRootId $id) : void
    {
        if ('' === $id->toString()) {
            throw new Exception\InvalidAggregateIdGiven($id);
        }
    }
}
