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
     * @var Event\Sourced[]
     */
    private $objects = [];

    public function __construct(Domain\EventStore $store)
    {
        $this->store = $store;
        $this->objects = new \SplObjectStorage();
    }

    public function add(Event\Sourced $object) : void
    {
        if (!$this->has($object)) {
            $this->objects->attach($object, $object->lastReplayed());
        }
    }

    public function remove(Event\Sourced $object) : void
    {
        $this->objects->detach($object);
    }

    public function has(Event\Sourced $object) : bool
    {
        return $this->objects->contains($object);
    }

    public function count() : int
    {
        return count($this->objects);
    }

    public function commit() : void
    {
        foreach ($this->objects as $object) {
            /* @var $object Event\Sourced */
            /* @var $last Event|null */
            $producerId = $object->producerId();
            $last = $this->objects->getInfo();
            $events = $object->events();
            $this->store->add($producerId, $last, ...$events);
        }

        $this->clear();
    }

    public function clear() : void
    {
        $this->objects = new \SplObjectStorage();
    }
}
