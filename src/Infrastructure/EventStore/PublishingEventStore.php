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

use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\EventBus;
use Streak\Domain\EventStore;
use Streak\Domain\Exception;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class PublishingEventStore implements EventStore
{
    private $store;
    private $bus;

    public function __construct(EventStore $store, EventBus $bus)
    {
        $this->store = $store;
        $this->bus = $bus;
    }

    public function producerId(Event $event) : Domain\Id
    {
        return $this->store->producerId($event);
    }

    public function add(Domain\Id $producerId, ?Event $last = null, Event ...$events) : void
    {
        if (0 === count($events)) {
            return;
        }

        $this->store->add($producerId, $last, ...$events);
        $this->bus->publish(...$events);
    }

    /**
     * @throws Exception\InvalidAggregateGiven
     */
    public function stream(Domain\Id ...$ids) : Event\FilterableStream
    {
        return $this->store->stream(...$ids);
    }

    public function log() : Event\Log
    {
        return $this->store->log();
    }
}
