<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Infrastructure\EventSourced;

use Streak\Domain;
use Streak\Domain\AggregateRoot;
use Streak\Domain\Exception;
use Streak\Infrastructure\EventSourced;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class Repository implements Domain\Repository
{
    /**
     * @var AggregateRoot\Factory
     */
    private $factory;

    /**
     * @var Domain\EventStore
     */
    private $store;

    /**
     * @var EventSourced\UnitOfWork
     */
    private $uow;

    public function __construct(AggregateRoot\Factory $factory, Domain\EventStore $store, EventSourced\UnitOfWork $uow)
    {
        $this->factory = $factory;
        $this->store   = $store;
        $this->uow     = $uow;
    }

    public function find(AggregateRoot\Id $id) : AggregateRoot
    {
        $aggregate = $this->factory->create($id);

        if (!$aggregate instanceof Domain\EventSourced\AggregateRoot) {
            throw new Exception\AggregateNotSupported($aggregate);
        }

        $events = $this->store->getEvents($aggregate);

        if (count($events) === 0) {
            throw new Exception\AggregateNotFound($id);
        }

        $aggregate->replayEvents(...$events);

        $this->uow->register($aggregate);

        return $aggregate;
    }

    public function add(AggregateRoot $aggregate) : void
    {
        if (!$aggregate instanceof Domain\EventSourced\AggregateRoot) {
            throw new Exception\AggregateNotSupported($aggregate);
        }

        $this->uow->register($aggregate);
    }
}
