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

namespace Streak\Infrastructure\Testing\Command;

use PHPUnit;
use Streak\Application;
use Streak\Domain;
use Streak\Domain\AggregateRoot;
use Streak\Infrastructure\AggregateRoot\Repository\EventSourcedRepository;
use Streak\Infrastructure\AggregateRoot\Snapshotter\NullSnapshotter;
use Streak\Infrastructure\EventStore\InMemoryEventStore;
use Streak\Infrastructure\UnitOfWork\EventStoreUnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @codeCoverageIgnore
 */
abstract class TestCase extends PHPUnit\Framework\TestCase
{
    private $store;
    private $snapshotter;
    private $uow;
    private $repository;
    private $scenarioExecuted;

    public function setUp()
    {
        $this->store = new InMemoryEventStore();
        $this->snapshotter = new NullSnapshotter();
        $this->uow = new EventStoreUnitOfWork($this->store);
        $this->repository = new EventSourcedRepository($this->createFactory(), $this->store, $this->snapshotter, $this->uow);
        $this->scenarioExecuted = false;
    }

    public function getRepository() : AggregateRoot\Repository
    {
        return $this->repository;
    }

    public function for(Domain\Id $id) : Scenario\Given
    {
        if (true === $this->scenarioExecuted) {
            $message = 'Scenario already executed.';
            throw new \BadMethodCallException($message);
        }

        $this->scenarioExecuted = true;

        return $this->createScenario()->for($id);
    }

    abstract protected function createFactory() : AggregateRoot\Factory;

    abstract protected function createHandler(Domain\EventStore $store) : Application\CommandHandler;

    private function createScenario() : Scenario
    {
        return new Scenario($this->createHandler($this->store), $this->store, $this->uow);
    }
}
