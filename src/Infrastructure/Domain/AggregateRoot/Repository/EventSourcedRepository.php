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

namespace Streak\Infrastructure\Domain\AggregateRoot\Repository;

use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Exception;
use Streak\Infrastructure\Domain\AggregateRoot;
use Streak\Infrastructure\Domain\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Domain\AggregateRoot\Repository\EventSourcedRepositoryTest
 */
class EventSourcedRepository implements Domain\AggregateRoot\Repository
{
    public function __construct(private Domain\AggregateRoot\Factory $factory, private Domain\EventStore $store, private AggregateRoot\Snapshotter $snapshotter, private UnitOfWork $uow)
    {
    }

    public function find(Domain\AggregateRoot\Id $id): ?Domain\AggregateRoot
    {
        $aggregate = $this->factory->create($id);

        if (!$aggregate instanceof Domain\Event\Sourced\AggregateRoot) {
            throw new Exception\ObjectNotSupported($aggregate);
        }

        $aggregate = $this->snapshotter->restoreToSnapshot($aggregate) ?: $aggregate;

        if (!$aggregate instanceof Domain\Event\Sourced\AggregateRoot) {
            throw new Exception\ObjectNotSupported($aggregate);
        }

        $snapshot = 0 !== $aggregate->version(); // was snapshot taken?

        $filter = new Domain\EventStore\Filter();
        $filter = $filter->filterProducerIds($id);

        $stream = $this->store->stream($filter);

        if ($aggregate->lastEvent()) {
            $stream = $stream->after($aggregate->lastEvent());
        }

        $aggregate->replay($stream);

        // aggregate does not exist
        if (0 === $aggregate->version()) {
            return null;
        }

        // no snapshot although events are found.
        // it can occur after snapshot storage was reset and, in that case, we don't want to wait for
        // next snapshotting window.
        if (false === $snapshot) {
            $this->snapshotter->takeSnapshot($aggregate);
        }

        $this->uow->add($aggregate);

        return $aggregate;
    }

    public function add(Domain\AggregateRoot $aggregate): void
    {
        if (!$aggregate instanceof Event\Sourced\AggregateRoot) {
            throw new Exception\ObjectNotSupported($aggregate);
        }

        $this->uow->add($aggregate);
    }
}
