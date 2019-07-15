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

namespace Streak\Infrastructure\AggregateRoot\Repository;

use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Exception;
use Streak\Infrastructure\AggregateRoot\Snapshotter;
use Streak\Infrastructure\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class EventSourcedRepository implements Domain\AggregateRoot\Repository
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
     * @var Snapshotter
     */
    private $snapshotter;

    /**
     * @var UnitOfWork
     */
    private $uow;

    public function __construct(Domain\AggregateRoot\Factory $factory, Domain\EventStore $store, Snapshotter $snapshotter, UnitOfWork $uow)
    {
        $this->factory = $factory;
        $this->store = $store;
        $this->snapshotter = $snapshotter;
        $this->uow = $uow;
    }

    public function find(Domain\AggregateRoot\Id $id) : ?Domain\AggregateRoot
    {
        $aggregate = $this->factory->create($id);

        if (!$aggregate instanceof Domain\Event\Sourced\AggregateRoot) {
            throw new Exception\ObjectNotSupported($aggregate);
        }

        $aggregate = $this->snapshotter->restoreToSnapshot($aggregate) ?: $aggregate;

        if (!$aggregate instanceof Domain\Event\Sourced\AggregateRoot) {
            throw new Exception\ObjectNotSupported($aggregate);
        }

        $filter = new Domain\EventStore\Filter();
        $filter = $filter->filterProducerIds($id);

        $stream = $this->store->stream($filter);

        if ($aggregate->lastEvent()) {
            $stream = $stream->after($aggregate->lastEvent());
        }

        if ($stream->empty()) {
            // aggregate from snapshot is fresh
            if ($aggregate->version() > 0) {
                $this->uow->add($aggregate);

                return $aggregate;
            }

            // aggregate does not exist
            if (0 === $aggregate->version()) {
                return null;
            }
        }

        $aggregate->replay($stream);

        $this->uow->add($aggregate);

        return $aggregate;
    }

    public function add(Domain\AggregateRoot $aggregate) : void
    {
        if (!$aggregate instanceof Event\Sourced\AggregateRoot) {
            throw new Exception\ObjectNotSupported($aggregate);
        }

        $this->uow->add($aggregate);
    }
}
