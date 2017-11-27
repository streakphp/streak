<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Infrastructure\Testing\Saga;

use PHPUnit;
use Streak\Application;
use Streak\Domain;
use Streak\Infrastructure\CommandHandler\SynchronousCommandBus;
use Streak\Infrastructure\EventBus\InMemoryCommandBus;
use Streak\Infrastructure\EventStore\InMemoryEventStore;
use Streak\Infrastructure\Repository\EventSourcedRepository;
use Streak\Infrastructure\Repository\UnitOfWork;
use Streak\Infrastructure\Testing\Scenario\When;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
abstract class TestCase extends PHPUnit\Framework\TestCase
{
    private $bus;

    public function setUp()
    {
        $this->bus = new SynchronousCommandBus();
    }

    private function createScenario() : Scenario
    {
        return new Scenario($this->getCommandBus(), $this->createSaga($this->getCommandBus()));
    }

    public function getCommandBus() : SynchronousCommandBus
    {
        return $this->bus;
    }

    public function given(Domain\Message ...$messages) : Scenario\When
    {
        return $this->createScenario()->given(...$messages);
    }

    abstract public function createSaga(Application\CommandBus $bus) : Application\Saga;
}
