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

use PHPUnit\Framework\TestCase;
use Streak\Domain;
use Streak\Domain\Event;
use Streak\Infrastructure;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\AggregateRoot\Repository\EventSourcedRepository
 */
class EventSourcedRepositoryTest extends TestCase
{
    private Domain\AggregateRoot\Factory $factory;
    private Domain\EventStore $store;

    private Infrastructure\Domain\AggregateRoot\Snapshotter $snapshotter;
    private Infrastructure\Domain\UnitOfWork $uow;

    private Domain\AggregateRoot $nonEventSourcedAggregateRoot;

    private Event\Sourced\AggregateRoot $aggregateRoot;
    private Domain\AggregateRoot\Id $aggregateRootId;

    private Domain\Event\Envelope $event1;

    private Domain\Event\Stream $stream;

    protected function setUp(): void
    {
        $this->factory = $this->getMockBuilder(Domain\AggregateRoot\Factory::class)->getMockForAbstractClass();
        $this->store = $this->getMockBuilder(Domain\EventStore::class)->getMockForAbstractClass();
        $this->snapshotter = $this->getMockBuilder(Infrastructure\Domain\AggregateRoot\Snapshotter::class)->getMockForAbstractClass();
        $this->uow = $this->getMockBuilder(Infrastructure\Domain\UnitOfWork::class)->getMockForAbstractClass();

        $this->nonEventSourcedAggregateRoot = $this->getMockBuilder(Domain\AggregateRoot::class)->getMockForAbstractClass();
        $this->aggregateRoot = $this->getMockBuilder(Event\Sourced\AggregateRoot::class)->getMockForAbstractClass();

        $this->aggregateRootId = $this->getMockBuilder(Domain\AggregateRoot\Id::class)->getMockForAbstractClass();

        $this->event1 = Event\Envelope::new($this->getMockBuilder(Domain\Event::class)->getMockForAbstractClass(), $this->aggregateRootId, 1);
        $this->stream = $this->getMockBuilder(Event\Stream::class)->getMockForAbstractClass();
    }

    public function testFindingNonEventSourcedAggregate(): void
    {
        $this->uow
            ->expects(self::never())
            ->method(self::anything())
        ;

        $this->factory
            ->expects(self::once())
            ->method('create')
            ->with($this->aggregateRootId)
            ->willReturn($this->nonEventSourcedAggregateRoot)
        ;

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->snapshotter, $this->uow);

        $exception = new Domain\Exception\ObjectNotSupported($this->nonEventSourcedAggregateRoot);
        $this->expectExceptionObject($exception);

        $repository->find($this->aggregateRootId);
    }

    public function testRestoringToNonEventSourcedAggregate(): void
    {
        $this->factory
            ->expects(self::once())
            ->method('create')
            ->with($this->aggregateRootId)
            ->willReturn($this->aggregateRoot)
        ;

        $this->snapshotter
            ->expects(self::once())
            ->method('restoreToSnapshot')
            ->with($this->aggregateRoot)
            ->willReturn($this->nonEventSourcedAggregateRoot)
        ;

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->snapshotter, $this->uow);

        $exception = new Domain\Exception\ObjectNotSupported($this->nonEventSourcedAggregateRoot);
        $this->expectExceptionObject($exception);

        $repository->find($this->aggregateRootId);
    }

    public function testFindingAggregateNotCompatibleWithStore(): void
    {
        $this->uow
            ->expects(self::never())
            ->method(self::anything())
        ;

        $this->factory
            ->expects(self::once())
            ->method('create')
            ->with($this->aggregateRootId)
            ->willReturn($this->nonEventSourcedAggregateRoot)
        ;

        $exception1 = new Domain\Exception\InvalidAggregateIdGiven($this->aggregateRootId);

        $this->store
            ->expects(self::never())
            ->method('stream')
            ->with(Domain\EventStore\Filter::nothing()->filterProducerIds($this->aggregateRootId))
            ->willThrowException($exception1)
        ;

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->snapshotter, $this->uow);

        $exception2 = new Domain\Exception\ObjectNotSupported($this->nonEventSourcedAggregateRoot, $exception1);
        $this->expectExceptionObject($exception2);

        $repository->find($this->aggregateRootId);
    }

    public function testFindingNonExistingAggregate(): void
    {
        $this->uow
            ->expects(self::never())
            ->method(self::anything())
        ;

        $this->factory
            ->expects(self::once())
            ->method('create')
            ->with($this->aggregateRootId)
            ->willReturn($this->aggregateRoot)
        ;

        $this->snapshotter
            ->expects(self::once())
            ->method('restoreToSnapshot')
            ->with($this->aggregateRoot)
            ->willReturn($this->aggregateRoot)
        ;

        $this->snapshotter
            ->expects(self::never())
            ->method('takeSnapshot')
        ;

        $this->store
            ->expects(self::once())
            ->method('stream')
            ->with(Domain\EventStore\Filter::nothing()->filterProducerIds($this->aggregateRootId))
            ->willReturn($this->stream)
        ;

        $this->aggregateRoot
            ->expects(self::atLeastOnce())
            ->method('version')
            ->with()
            ->willReturn(0)
        ;

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->snapshotter, $this->uow);

        $aggregate = $repository->find($this->aggregateRootId);

        self::assertNull($aggregate);
    }

    public function testFindingAggregateWithStaleSnapshot(): void
    {
        $this->factory
            ->expects(self::once())
            ->method('create')
            ->with($this->aggregateRootId)
            ->willReturn($this->aggregateRoot)
        ;

        $this->snapshotter
            ->expects(self::once())
            ->method('restoreToSnapshot')
            ->with($this->aggregateRoot)
            ->willReturn($this->aggregateRoot)
        ;

        $this->snapshotter
            ->expects(self::never())
            ->method('takeSnapshot')
        ;

        $this->aggregateRoot
            ->expects(self::atLeastOnce())
            ->method('lastEvent')
            ->with()
            ->willReturn($this->event1)
        ;

        $this->aggregateRoot
            ->expects(self::atLeastOnce())
            ->method('version')
            ->with()
            ->willReturnOnConsecutiveCalls(10, 12)
        ;

        $this->store
            ->expects(self::once())
            ->method('stream')
            ->with(Domain\EventStore\Filter::nothing()->filterProducerIds($this->aggregateRootId))
            ->willReturn($this->stream)
        ;

        $this->stream
            ->expects(self::once())
            ->method('after')
            ->with($this->event1)
            ->willReturnSelf()
        ;

        $this->aggregateRoot
            ->expects(self::once())
            ->method('replay')
            ->with($this->stream)
        ;

        $this->uow
            ->expects(self::once())
            ->method('add')
            ->with($this->aggregateRoot)
        ;

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->snapshotter, $this->uow);

        $aggregate = $repository->find($this->aggregateRootId);

        self::assertSame($this->aggregateRoot, $aggregate);
    }

    public function testFindingAggregateWithFreshSnapshot(): void
    {
        $this->factory
            ->expects(self::once())
            ->method('create')
            ->with($this->aggregateRootId)
            ->willReturn($this->aggregateRoot)
        ;

        $this->snapshotter
            ->expects(self::once())
            ->method('restoreToSnapshot')
            ->with($this->aggregateRoot)
            ->willReturn($this->aggregateRoot)
        ;

        $this->snapshotter
            ->expects(self::never())
            ->method('takeSnapshot')
        ;

        $this->aggregateRoot
            ->expects(self::atLeastOnce())
            ->method('lastEvent')
            ->with()
            ->willReturn($this->event1)
        ;

        $this->aggregateRoot
            ->expects(self::atLeastOnce())
            ->method('version')
            ->with()
            ->willReturnOnConsecutiveCalls(10, 10)
        ;

        $this->store
            ->expects(self::once())
            ->method('stream')
            ->with(Domain\EventStore\Filter::nothing()->filterProducerIds($this->aggregateRootId))
            ->willReturn($this->stream)
        ;

        $this->stream
            ->expects(self::once())
            ->method('after')
            ->with($this->event1)
            ->willReturnSelf()
        ;

        $this->aggregateRoot
            ->expects(self::atLeastOnce())
            ->method('version')
            ->willReturn(1)
        ;

        $this->aggregateRoot
            ->expects(self::once())
            ->method('replay')
            ->with($this->stream)
        ;

        $this->uow
            ->expects(self::once())
            ->method('add')
            ->with($this->aggregateRoot)
        ;

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->snapshotter, $this->uow);

        $aggregate = $repository->find($this->aggregateRootId);

        self::assertSame($this->aggregateRoot, $aggregate);
    }

    public function testFindingAggregateWithoutSnapshot(): void
    {
        $this->factory
            ->expects(self::once())
            ->method('create')
            ->with($this->aggregateRootId)
            ->willReturn($this->aggregateRoot)
        ;

        $this->snapshotter
            ->expects(self::once())
            ->method('restoreToSnapshot')
            ->with($this->aggregateRoot)
            ->willReturn(null)
        ;

        $this->snapshotter
            ->expects(self::once())
            ->method('takeSnapshot')
            ->with($this->aggregateRoot)
        ;

        $this->store
            ->expects(self::once())
            ->method('stream')
            ->with(Domain\EventStore\Filter::nothing()->filterProducerIds($this->aggregateRootId))
            ->willReturn($this->stream)
        ;

        $this->aggregateRoot
            ->expects(self::atLeastOnce())
            ->method('version')
            ->with()
            ->willReturnOnConsecutiveCalls(
                0,
                12
            )
        ;

        $this->aggregateRoot
            ->expects(self::once())
            ->method('replay')
            ->with($this->stream)
        ;

        $this->uow
            ->expects(self::once())
            ->method('add')
            ->with($this->aggregateRoot)
        ;

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->snapshotter, $this->uow);

        $aggregate = $repository->find($this->aggregateRootId);

        self::assertSame($this->aggregateRoot, $aggregate);
    }

    public function testAddingNonEventSourcedAggregate(): void
    {
        $this->uow
            ->expects(self::never())
            ->method(self::anything())
        ;

        $exception = new Domain\Exception\ObjectNotSupported($this->nonEventSourcedAggregateRoot);
        $this->expectExceptionObject($exception);

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->snapshotter, $this->uow);
        $repository->add($this->nonEventSourcedAggregateRoot);
    }

    public function testAddingAggregate(): void
    {
        $this->uow
            ->expects(self::once())
            ->method('add')
            ->with($this->aggregateRoot)
        ;

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->snapshotter, $this->uow);
        $repository->add($this->aggregateRoot);
    }
}
