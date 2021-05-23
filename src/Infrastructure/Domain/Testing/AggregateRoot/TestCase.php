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

namespace Streak\Infrastructure\Domain\Testing\AggregateRoot;

use Streak\Application\CommandHandler\AggregateRootHandler;
use Streak\Domain;
use Streak\Infrastructure\Domain\AggregateRoot\Repository\EventSourcedRepository;
use Streak\Infrastructure\Domain\AggregateRoot\Snapshotter;
use Streak\Infrastructure\Domain\EventStore\InMemoryEventStore;
use Streak\Infrastructure\Domain\Serializer;
use Streak\Infrastructure\Domain\Serializer\PhpSerializer;
use Streak\Infrastructure\Domain\UnitOfWork;
use Streak\Infrastructure\Domain\UnitOfWork\EventStoreUnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @codeCoverageIgnore
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    public function for(Domain\AggregateRoot\Id $id): Scenario\Given
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

    abstract protected function createFactory(): Domain\AggregateRoot\Factory;

    protected function createHandler(Domain\AggregateRoot\Factory $factory, Domain\AggregateRoot\Repository $repository): Domain\CommandHandler
    {
        return new AggregateRootHandler($repository);
    }

    protected function createSnapshotterSerializer(): Serializer
    {
        return new PhpSerializer();
    }

    protected function createSnapshotterStorage()
    {
        return new Snapshotter\Storage\InMemoryStorage();
    }

    protected function createSnapshotter(Serializer $serializer, Snapshotter\Storage $storage): Snapshotter
    {
        return new Snapshotter\SnapshottableAggregatesSnapshotter($serializer, $storage);
    }

    private function createScenario(Domain\CommandHandler $handler, InMemoryEventStore $store, Domain\AggregateRoot\Factory $factory, Snapshotter $snapshotter, UnitOfWork $uow): Scenario
    {
        return new Scenario($handler, $store, $factory, $snapshotter, $uow);
    }
}
