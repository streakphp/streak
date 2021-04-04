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

namespace Streak\Infrastructure\Testing\AggregateRoot;

use Streak\Application;
use Streak\Application\CommandHandler;
use Streak\Domain\AggregateRoot;
use Streak\Infrastructure\AggregateRoot\Repository\EventSourcedRepository;
use Streak\Infrastructure\AggregateRoot\Snapshotter;
use Streak\Infrastructure\EventStore\InMemoryEventStore;
use Streak\Infrastructure\Serializer;
use Streak\Infrastructure\Serializer\PhpSerializer;
use Streak\Infrastructure\UnitOfWork;
use Streak\Infrastructure\UnitOfWork\EventStoreUnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @codeCoverageIgnore
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    public function for(AggregateRoot\Id $id) : Scenario\Given
    {
        $factory = $this->createFactory();
        $store = new InMemoryEventStore();
        $serializer = $this->createSnapshotterSerializer();
        $storage = $this->createSnapshotterStorage();
        $snapshotter = $this->createSnapshotter($serializer, $storage);
        $uow = new EventStoreUnitOfWork($store);
        $repository = new EventSourcedRepository($factory, $store, $snapshotter, $uow);
        $handler = $this->createHandler($factory, $repository);

        return $this
            ->createScenario($handler, $store, $factory, $snapshotter, $uow)
            ->for($id)
        ;
    }

    abstract protected function createFactory() : AggregateRoot\Factory;

    protected function createHandler(AggregateRoot\Factory $factory, AggregateRoot\Repository $repository) : Application\CommandHandler
    {
        return new CommandHandler\AggregateRootHandler($repository);
    }

    protected function createSnapshotterSerializer() : Serializer
    {
        return new PhpSerializer();
    }

    protected function createSnapshotterStorage()
    {
        return new Snapshotter\Storage\InMemoryStorage();
    }

    protected function createSnapshotter(Serializer $serializer, Snapshotter\Storage $storage) : Snapshotter
    {
        return new Snapshotter\SnapshottableAggregatesSnapshotter($serializer, $storage);
    }

    private function createScenario(Application\CommandHandler $handler, InMemoryEventStore $store, AggregateRoot\Factory $factory, Snapshotter $snapshotter, UnitOfWork $uow) : Scenario
    {
        return new Scenario($handler, $store, $factory, $snapshotter, $uow);
    }
}
