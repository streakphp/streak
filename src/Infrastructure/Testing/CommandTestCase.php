<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Infrastructure\Testing;

use PHPUnit\Framework\TestCase;
use Streak\Application;
use Streak\Domain;
use Streak\Infrastructure\EventStore\InMemoryEventStore;
use Streak\Infrastructure\Repository\EventSourcedRepository;
use Streak\Infrastructure\Repository\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
abstract class CommandTestCase extends TestCase
{
    /**
     * @var Domain\AggregateRootId
     */
    private $id;

    /**
     * @var InMemoryEventStore
     */
    private $store;

    /**
     * @var UnitOfWork
     */
    private $uow;

    public function setUp()
    {
        $this->store = new InMemoryEventStore();
        $this->uow = new UnitOfWork($this->store);
    }

    public function getRepository() : Domain\Repository
    {
        return new EventSourcedRepository($this->createFactory(), $this->store, $this->uow);
    }

    public function with(Domain\AggregateRootId $id)
    {
        $this->id = $id;
    }

    public function given(Domain\Event ...$events)
    {
        if (\count($events) === 0) {
            return;
        }

        $aggregate = $this->createFactory()->create($this->id);

        $this->store->addEvents($aggregate, ...$events);
    }

    public function when(Application\Command $command)
    {
        $handler = $this->createHandler($this->store);
        $handler->handle($command);
    }

    public function then(Domain\Event ...$expected)
    {
        $this->store->clear();
        $this->uow->commit();

        $aggregate = $this->createFactory()->create($this->id);
        $actual = $this->store->getEvents($aggregate);

        self::assertEquals($expected, $actual);
    }

    abstract protected function createFactory() : Domain\AggregateRootFactory;

    abstract protected function createHandler(Domain\EventStore $store) : Application\CommandHandler;
}
