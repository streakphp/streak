<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Streak\Infrastructure\Repository;

use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Exception;
use Streak\Infrastructure;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class EventSourcedRepository implements Domain\Repository
{
    /**
     * @var Domain\AggregateRoot\Factory
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

    public function __construct(Domain\AggregateRoot\Factory $factory, Domain\EventStore $store, Infrastructure\UnitOfWork $uow)
    {
        $this->factory = $factory;
        $this->store   = $store;
        $this->uow     = $uow;
    }

    public function find(Domain\AggregateRoot\Id $id) : ?Domain\AggregateRoot
    {
        $aggregate = $this->factory->create($id);

        if (!$aggregate instanceof Domain\Event\Sourced\AggregateRoot) {
            throw new Exception\AggregateNotSupported($aggregate);
        }

        $events = $this->store->find($id);

        if (\count($events) === 0) {
            return null;
        }

        $aggregate->replay(...$events);

        $this->uow->add($aggregate);

        return $aggregate;
    }

    public function add(Domain\AggregateRoot $aggregate) : void
    {
        if (!$aggregate instanceof Event\Sourced\AggregateRoot) {
            throw new Exception\AggregateNotSupported($aggregate);
        }

        $this->uow->add($aggregate);
    }
}
