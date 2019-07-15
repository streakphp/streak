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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain;
use Streak\Domain\Event;
use Streak\Infrastructure;
use Streak\Infrastructure\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\AggregateRoot\Repository\EventSourcedRepository
 */
class EventSourcedRepositoryTest extends TestCase
{
    /**
     * @var Domain\AggregateRoot\Factory|MockObject
     */
    private $factory;

    /**
     * @var Domain\EventStore|MockObject
     */
    private $store;

    /**
     * @var Infrastructure\AggregateRoot\Snapshotter|MockObject
     */
    private $snapshotter;

    /**
     * @var UnitOfWork|MockObject
     */
    private $uow;

    /**
     * @var Domain\AggregateRoot|MockObject
     */
    private $nonEventSourcedAggregateRoot;

    /**
     * @var Event\Sourced\AggregateRoot|MockObject
     */
    private $aggregateRoot;

    /**
     * @var Event\Sourced\AggregateRoot\Id|MockObject
     */
    private $aggregateRootId;

    /**
     * @var Domain\Event|MockObject
     */
    private $event1;

    /**
     * @var Domain\Event|MockObject
     */
    private $event2;

    /**
     * @var Domain\Event|MockObject
     */
    private $event3;

    /**
     * @var Domain\Event|MockObject
     */
    private $event4;

    /**
     * @var Event\Stream|MockObject
     */
    private $stream;

    public function setUp()
    {
        $this->factory = $this->getMockBuilder(Domain\AggregateRoot\Factory::class)->getMockForAbstractClass();
        $this->store = $this->getMockBuilder(Domain\EventStore::class)->getMockForAbstractClass();
        $this->snapshotter = $this->getMockBuilder(Infrastructure\AggregateRoot\Snapshotter::class)->getMockForAbstractClass();
        $this->uow = $this->getMockBuilder(UnitOfWork::class)->getMockForAbstractClass();

        $this->nonEventSourcedAggregateRoot = $this->getMockBuilder(Domain\AggregateRoot::class)->getMockForAbstractClass();
        $this->aggregateRoot = $this->getMockBuilder(Event\Sourced\AggregateRoot::class)->getMockForAbstractClass();

        $this->aggregateRootId = $this->getMockBuilder(Event\Sourced\AggregateRoot\Id::class)->getMockForAbstractClass();

        $this->event1 = $this->getMockBuilder(Domain\Event::class)->getMockForAbstractClass();
        $this->event2 = $this->getMockBuilder(Domain\Event::class)->getMockForAbstractClass();
        $this->event3 = $this->getMockBuilder(Domain\Event::class)->getMockForAbstractClass();
        $this->event4 = $this->getMockBuilder(Domain\Event::class)->getMockForAbstractClass();
        $this->stream = $this->getMockBuilder(Event\Stream::class)->getMockForAbstractClass();
    }

    public function testFindingNonEventSourcedAggregate()
    {
        $this->uow
            ->expects($this->never())
            ->method($this->anything())
        ;

        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($this->aggregateRootId)
            ->willReturn($this->nonEventSourcedAggregateRoot)
        ;

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->snapshotter, $this->uow);

        $exception = new Domain\Exception\ObjectNotSupported($this->nonEventSourcedAggregateRoot);
        $this->expectExceptionObject($exception);

        $repository->find($this->aggregateRootId);
    }

    public function testRestoringToNonEventSourcedAggregate()
    {
        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($this->aggregateRootId)
            ->willReturn($this->aggregateRoot)
        ;

        $this->snapshotter
            ->expects($this->once())
            ->method('restoreToSnapshot')
            ->with($this->aggregateRoot)
            ->willReturn($this->nonEventSourcedAggregateRoot)
        ;

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->snapshotter, $this->uow);

        $exception = new Domain\Exception\ObjectNotSupported($this->nonEventSourcedAggregateRoot);
        $this->expectExceptionObject($exception);

        $repository->find($this->aggregateRootId);
    }

    public function testFindingAggregateNotCompatibleWithStore()
    {
        $this->uow
            ->expects($this->never())
            ->method($this->anything())
        ;

        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($this->aggregateRootId)
            ->willReturn($this->nonEventSourcedAggregateRoot)
        ;

        $exception1 = new Domain\Exception\InvalidAggregateIdGiven($this->aggregateRootId);

        $this->store
            ->expects($this->never())
            ->method('stream')
            ->with(Domain\EventStore\Filter::nothing()->filterProducerIds($this->aggregateRootId))
            ->willThrowException($exception1)
        ;

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->snapshotter, $this->uow);

        $exception2 = new Domain\Exception\ObjectNotSupported($this->nonEventSourcedAggregateRoot, $exception1);
        $this->expectExceptionObject($exception2);

        $repository->find($this->aggregateRootId);
    }

    public function testFindingNonExistingAggregate()
    {
        $this->uow
            ->expects($this->never())
            ->method($this->anything())
        ;

        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($this->aggregateRootId)
            ->willReturn($this->aggregateRoot)
        ;

        $this->snapshotter
            ->expects($this->once())
            ->method('restoreToSnapshot')
            ->with($this->aggregateRoot)
            ->willReturn($this->aggregateRoot)
        ;

        $this->store
            ->expects($this->once())
            ->method('stream')
            ->with(Domain\EventStore\Filter::nothing()->filterProducerIds($this->aggregateRootId))
            ->willReturn($this->stream)
        ;

        $this->aggregateRoot
            ->expects($this->atLeastOnce())
            ->method('version')
            ->willReturn(0)
        ;

        $this->stream
            ->expects($this->once())
            ->method('empty')
            ->willReturn(true)
        ;

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->snapshotter, $this->uow);

        $aggregate = $repository->find($this->aggregateRootId);

        $this->assertNull($aggregate);
    }

    public function testFindingAggregateWithStaleSnapshot()
    {
        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($this->aggregateRootId)
            ->willReturn($this->aggregateRoot)
        ;

        $this->snapshotter
            ->expects($this->once())
            ->method('restoreToSnapshot')
            ->with($this->aggregateRoot)
            ->willReturn($this->aggregateRoot)
        ;

        $this->aggregateRoot
            ->expects($this->atLeastOnce())
            ->method('lastEvent')
            ->with()
            ->willReturn($this->event1)
        ;

        $this->store
            ->expects($this->once())
            ->method('stream')
            ->with(Domain\EventStore\Filter::nothing()->filterProducerIds($this->aggregateRootId))
            ->willReturn($this->stream)
        ;

        $this->stream
            ->expects($this->once())
            ->method('after')
            ->with($this->event1)
            ->willReturnSelf()
        ;

        $this->stream
            ->expects($this->once())
            ->method('empty')
            ->willReturn(false)
        ;

        $this->aggregateRoot
            ->expects($this->once())
            ->method('replay')
            ->with($this->stream)
        ;

        $this->uow
            ->expects($this->once())
            ->method('add')
            ->with($this->aggregateRoot)
        ;

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->snapshotter, $this->uow);

        $aggregate = $repository->find($this->aggregateRootId);

        $this->assertSame($this->aggregateRoot, $aggregate);
    }

    public function testFindingAggregateWithFreshSnapshot()
    {
        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($this->aggregateRootId)
            ->willReturn($this->aggregateRoot)
        ;

        $this->snapshotter
            ->expects($this->once())
            ->method('restoreToSnapshot')
            ->with($this->aggregateRoot)
            ->willReturn($this->aggregateRoot)
        ;

        $this->aggregateRoot
            ->expects($this->atLeastOnce())
            ->method('lastEvent')
            ->with()
            ->willReturn($this->event1)
        ;

        $this->store
            ->expects($this->once())
            ->method('stream')
            ->with(Domain\EventStore\Filter::nothing()->filterProducerIds($this->aggregateRootId))
            ->willReturn($this->stream)
        ;

        $this->stream
            ->expects($this->once())
            ->method('after')
            ->with($this->event1)
            ->willReturnSelf()
        ;

        $this->stream
            ->expects($this->once())
            ->method('empty')
            ->willReturn(true)
        ;

        $this->aggregateRoot
            ->expects($this->atLeastOnce())
            ->method('version')
            ->willReturn(1)
        ;

        $this->aggregateRoot
            ->expects($this->never())
            ->method('replay')
            ->with($this->stream)
        ;

        $this->uow
            ->expects($this->once())
            ->method('add')
            ->with($this->aggregateRoot)
        ;

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->snapshotter, $this->uow);

        $aggregate = $repository->find($this->aggregateRootId);

        $this->assertSame($this->aggregateRoot, $aggregate);
    }

    public function testFindingAggregateWithoutSnapshot()
    {
        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($this->aggregateRootId)
            ->willReturn($this->aggregateRoot)
        ;

        $this->snapshotter
            ->expects($this->once())
            ->method('restoreToSnapshot')
            ->with($this->aggregateRoot)
            ->willReturn(null)
        ;

        $this->store
            ->expects($this->once())
            ->method('stream')
            ->with(Domain\EventStore\Filter::nothing()->filterProducerIds($this->aggregateRootId))
            ->willReturn($this->stream)
        ;

        $this->stream
            ->expects($this->once())
            ->method('empty')
            ->willReturn(false)
        ;

        $this->aggregateRoot
            ->expects($this->once())
            ->method('replay')
            ->with($this->stream)
        ;

        $this->uow
            ->expects($this->once())
            ->method('add')
            ->with($this->aggregateRoot)
        ;

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->snapshotter, $this->uow);

        $aggregate = $repository->find($this->aggregateRootId);

        $this->assertSame($this->aggregateRoot, $aggregate);
    }

    public function testAddingNonEventSourcedAggregate()
    {
        $this->uow
            ->expects($this->never())
            ->method($this->anything())
        ;

        $exception = new Domain\Exception\ObjectNotSupported($this->nonEventSourcedAggregateRoot);
        $this->expectExceptionObject($exception);

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->snapshotter, $this->uow);
        $repository->add($this->nonEventSourcedAggregateRoot);
    }

    public function testAddingAggregate()
    {
        $this->uow
            ->expects($this->once())
            ->method('add')
            ->with($this->aggregateRoot)
        ;

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->snapshotter, $this->uow);
        $repository->add($this->aggregateRoot);
    }
}
