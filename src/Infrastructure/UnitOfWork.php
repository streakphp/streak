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
        $this->producers = [];
    }

    public function add(Event\Producer $producer) : void
    {
        if (!$this->has($producer)) {
            $this->producers[] = [$producer, $producer->last()];
        }
    }

    public function remove(Event\Producer $producer) : void
    {
        foreach ($this->producers as $key => [$current, $last]) {
            if ($current === $producer) {
                unset($this->producers[$key]);

                return;
            }
        }
    }

    public function has(Event\Producer $producer) : bool
    {
        foreach ($this->producers as $key => [$current, $last]) {
            if ($current === $producer) {
                return true;
            }
        }

        return false;
    }

    public function count() : int
    {
        return count($this->producers);
    }

    public function commit() : void
    {
        while ($pair = array_shift($this->producers)) {
            [$producer, $last] = $pair;

            try {
                $producerId = $producer->producerId();
                $events = $producer->events();

                $this->store->add($producerId, $last, ...$events);
            } catch (\Exception $e) {
                array_unshift($this->producers, [$producer, $last]);
                throw $e;
            }
        }

        $this->clear();
    }

    public function clear() : void
    {
        $this->producers = [];
    }
}
