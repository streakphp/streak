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
use Streak\Infrastructure;

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
     * @var Infrastructure\UnitOfWork
     */
    private $uow;

    public function __construct(Domain\AggregateRoot\Factory $factory, Domain\EventStore $store, Infrastructure\UnitOfWork $uow)
    {
        $this->factory = $factory;
        $this->store = $store;
        $this->uow = $uow;
    }

    public function find(Domain\AggregateRoot\Id $id) : ?Domain\AggregateRoot
    {
        $aggregate = $this->factory->create($id);

        if (!$aggregate instanceof Domain\Event\Sourced\AggregateRoot) {
            throw new Exception\ObjectNotSupported($aggregate);
        }

        $stream = $this->store->stream($id);

        if ($stream->empty()) {
            return null;
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
