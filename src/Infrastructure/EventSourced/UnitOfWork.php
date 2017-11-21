<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Infrastructure\EventSourced;

use Domain;
use Domain\EventSourced;
use Infrastructure;

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
     * @var EventSourced\AggregateRoot[]
     */
    private $aggregates = [];

    public function __construct(Domain\EventStore $store)
    {
        $this->store = $store;
    }

    public function register(EventSourced\AggregateRoot $aggregate) : void
    {
        foreach ($this->aggregates as $current) {
            if ($current->equals($aggregate)) {
                return;
            }
        }

        $this->aggregates[] = $aggregate;
    }

    public function remove(EventSourced\AggregateRoot $aggregate) : void
    {
        foreach ($this->aggregates as $key => $current) {
            if ($current->equals($aggregate)) {
                unset($this->aggregates[$key]);
                break;
            }
        }
    }

    public function count() : int
    {
        return count($this->aggregates);
    }

    public function commit() : void
    {
        foreach ($this->aggregates as $aggregate) {
            $this->store->addEvents($aggregate, ...$aggregate->events());
        }

        $this->clear();
    }

    public function clear() : void
    {
        $this->aggregates = [];
    }
}
