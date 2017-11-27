<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Infrastructure\Repository;

use Streak\Domain;
use Streak\Domain\Exception;
use Streak\Infrastructure;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class EventSourcedRepository implements Domain\Repository
{
    /**
     * @var Domain\AggregateRootFactory
     */
    private $factory;

    /**
     * @var Domain\EventStore
     */
    private $store;

    /**
     * @var Infrastructure\UnitOfWork
     */
    private $uow;

    public function __construct(Domain\AggregateRootFactory $factory, Domain\EventStore $store, Infrastructure\UnitOfWork $uow)
    {
        $this->factory = $factory;
        $this->store   = $store;
        $this->uow     = $uow;
    }

    public function find(Domain\AggregateRootId $id) : ?Domain\AggregateRoot
    {
        $aggregate = $this->factory->create($id);

        if (!$aggregate instanceof Domain\EventSourced\AggregateRoot) {
            throw new Exception\AggregateNotSupported($aggregate);
        }

        try {
            $events = $this->store->getEvents($aggregate);
        } catch (Exception\InvalidAggregateGiven $e) {
            throw new Exception\AggregateNotSupported($aggregate, $e);
        }

        if (count($events) === 0) {
            return null;
        }

        $aggregate->replayEvents(...$events);

        $this->uow->add($aggregate);

        return $aggregate;
    }

    public function add(Domain\AggregateRoot $aggregate) : void
    {
        if (!$aggregate instanceof Domain\EventSourced\AggregateRoot) {
            throw new Exception\AggregateNotSupported($aggregate);
        }

        $this->uow->add($aggregate);
    }
}
