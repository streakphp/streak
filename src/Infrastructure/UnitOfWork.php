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

namespace Streak\Infrastructure;

use Streak\Domain;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class UnitOfWork
{
    /**
     * @var Domain\EventStore
     */
    private $store;

    /**
     * @var Event\Producer[]
     */
    private $producers = [];

    public function __construct(Domain\EventStore $store)
    {
        $this->store = $store;
        $this->producers = new \SplObjectStorage();
    }

    public function add(Event\Producer $producer) : void
    {
        if (!$this->has($producer)) {
            $this->producers->attach($producer, $producer->last());
        }
    }

    public function remove(Event\Producer $producer) : void
    {
        $this->producers->detach($producer);
    }

    public function has(Event\Producer $producer) : bool
    {
        return $this->producers->contains($producer);
    }

    public function count() : int
    {
        return count($this->producers);
    }

    public function commit() : void
    {
        foreach ($this->producers as $producer) {
            $producerId = $producer->producerId();
            $last = $this->producers->getInfo();
            $events = $producer->events();

            $this->store->add($producerId, $last, ...$events);
        }

        $this->clear();
    }

    public function clear() : void
    {
        $this->producers = new \SplObjectStorage();
    }
}
