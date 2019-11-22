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

namespace Streak\Infrastructure\EventStore;

use Streak\Domain\Event;
use Streak\Domain\EventBus;
use Streak\Domain\EventStore;
use Streak\Domain\Exception;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class PublishingEventStore implements EventStore, Schemable
{
    private $store;
    private $bus;
    private $working = false;
    private $events = [];

    public function __construct(EventStore $store, EventBus $bus)
    {
        $this->store = $store;
        $this->bus = $bus;
    }

    public function add(Event\Envelope ...$events) : void
    {
        if (0 === count($events)) {
            return;
        }

        $this->events = $events;

        if (false === $this->working) {
            $this->working = true;
            while (0 !== count($this->events)) {
                $events = $this->events;
                $this->events = [];
                try {
                    $this->store->add(...$events);
                    $this->bus->publish(...$events);
                } finally {
                    $this->working = false;
                }
            }
        }
    }

    /**
     * @throws Exception\InvalidAggregateGiven
     */
    public function stream(?EventStore\Filter $filter = null) : Event\Stream
    {
        return $this->store->stream($filter);
    }

    public function schema() : ?Schema
    {
        if ($this->store instanceof Schemable) {
            return $this->store->schema();
        }

        return null;
    }
}
