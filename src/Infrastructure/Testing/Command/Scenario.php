<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Infrastructure\Testing\Command;

use PHPUnit\Framework\Assert;
use Streak\Application;
use Streak\Domain;
use Streak\Infrastructure\EventStore\InMemoryEventStore;
use Streak\Infrastructure\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class Scenario implements Scenario\Given, Scenario\When, Scenario\Then
{
    private $factory;
    private $handler;
    private $store;
    private $uow;

    public function __construct(Domain\AggregateRoot\Factory $factory, Application\CommandHandler $handler, InMemoryEventStore $store, UnitOfWork $uow)
    {
        $this->factory = $factory;
        $this->handler = $handler;
        $this->store = $store;
        $this->uow = $uow;
    }

    public function given(Domain\Event ...$events) : Scenario\When
    {
        $this->store->add(...$events);

        return $this;
    }

    public function when(Application\Command $command) : Scenario\Then
    {
        $this->handler->handle($command);

        return $this;
    }

    public function then(Domain\Event ...$expected) : void
    {
        $this->store->clear();
        $this->uow->commit();

        $actual = $this->store->all();

        Assert::assertEquals($expected, $actual);
    }
}
