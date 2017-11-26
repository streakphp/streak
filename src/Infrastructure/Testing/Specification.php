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

use PHPUnit\Framework\Assert;
use Streak\Application;
use Streak\Domain;
use Streak\Infrastructure\EventStore\InMemoryEventStore;
use Streak\Infrastructure;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class Specification implements Given, Then, When
{
    private $id;
    private $factory;
    private $handler;
    private $store;
    private $uow;

    public function __construct(Domain\AggregateRootId $id, Domain\AggregateRootFactory $factory, Application\CommandHandler $handler, InMemoryEventStore $store, Infrastructure\UnitOfWork $uow)
    {
        $this->id = $id;
        $this->factory = $factory;
        $this->handler = $handler;
        $this->store = $store;
        $this->uow = $uow;
    }

    public function given(Domain\Event ...$events) : When
    {
        if (\count($events) === 0) {
            return $this;
        }

        $aggregate = $this->factory->create($this->id);

        $this->store->addEvents($aggregate, ...$events);

        return $this;
    }

    public function when(Application\Command $command) : Then
    {
        $this->handler->handle($command);

        return $this;
    }

    public function then(Domain\Event ...$expected) : void
    {
        $this->store->clear();
        $this->uow->commit();

        $aggregate = $this->factory->create($this->id);
        $actual = $this->store->getEvents($aggregate);

        Assert::assertEquals($expected, $actual);
    }
}

interface Given
{
    public function given(Domain\Event ...$events) : When;
}

interface When
{
    public function when(Application\Command $command) : Then;
}

interface Then
{
    public function then(Domain\Event ...$events) : void;
}
