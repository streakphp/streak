<?php

declare(strict_types=1);

/**
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
    }

    public function add(Event\Sourced $object) : void
    {
        foreach ($this->objects as $current) {
            if ($current->equals($object)) {
                return;
            }
        }

        $this->objects[] = $object;
    }

    public function remove(Event\Sourced $object) : void
    {
        foreach ($this->objects as $key => $current) {
            if ($current->equals($object)) {
                unset($this->objects[$key]);

                break;
            }
        }
    }

    public function has(Event\Sourced $object) : bool
    {
        foreach ($this->objects as $current) {
            if ($current->equals($object)) {
                return true;
            }
        }

        return false;
    }

    public function count() : int
    {
        return count($this->objects);
    }

    public function commit() : void
    {
        $events = [];
        foreach ($this->objects as $object) {
            foreach ($object->events() as $event) {
                $events[] = $event;
            }
        }

        $this->store->add(...$events);

        $this->clear();
    }

    public function clear() : void
    {
        $this->objects = [];
    }
}
