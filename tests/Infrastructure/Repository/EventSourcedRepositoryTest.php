<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Infrastructure\Repository;

use PHPUnit\Framework\TestCase;
use Streak\Domain;
use Streak\Infrastructure;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Repository\EventSourcedRepository
 */
class EventSourcedRepositoryTest extends TestCase
{
    /**
     * @var Domain\AggregateRootFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $factory;

    /**
     * @var Domain\EventStore|\PHPUnit_Framework_MockObject_MockObject
     */
    private $store;

    /**
     * @var Infrastructure\UnitOfWork
     */
    private $uow;

    /**
     * @var Domain\AggregateRoot|\PHPUnit_Framework_MockObject_MockObject
     */
    private $nonEventSourcedAggregate;

    /**
     * @var Domain\EventSourced\AggregateRoot|\PHPUnit_Framework_MockObject_MockObject
     */
    private $aggregate;

    /**
     * @var Domain\AggregateRootId|\PHPUnit_Framework_MockObject_MockObject
     */
    private $aggregateId;

    public function setUp()
    {
        $this->factory = $this->getMockBuilder(Domain\AggregateRootFactory::class)->getMockForAbstractClass();
        $this->store = $this->getMockBuilder(Domain\EventStore::class)->getMockForAbstractClass();
        $this->uow = new Infrastructure\UnitOfWork($this->store);

        $this->nonEventSourcedAggregate = $this->getMockBuilder(Domain\AggregateRoot::class)->disableOriginalConstructor()->getMockForAbstractClass();
        $this->aggregate = $this->getMockBuilder(Domain\EventSourced\AggregateRoot::class)->disableOriginalConstructor()->getMockForAbstractClass();

        $this->aggregateId = $this->getMockBuilder(Domain\AggregateRootId::class)->getMockForAbstractClass();
    }

    public function testFindingNonEventSourcedAggregate()
    {
        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($this->aggregateId)
            ->willReturn($this->nonEventSourcedAggregate);

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->uow);

        $exception = new Domain\Exception\AggregateNotSupported($this->nonEventSourcedAggregate);
        $this->expectExceptionObject($exception);

        $repository->find($this->aggregateId);
    }

    public function testFindingAggregateNotCompatibleWithStore()
    {
        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($this->aggregateId)
            ->willReturn($this->nonEventSourcedAggregate);

        $exception1 = new Domain\Exception\InvalidAggregateIdGiven($this->aggregateId);

        $this->store
            ->expects($this->never())
            ->method('find')
            ->with($this->aggregateId)
            ->willThrowException($exception1);

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->uow);

        $exception2 = new Domain\Exception\AggregateNotSupported($this->nonEventSourcedAggregate, $exception1);
        $this->expectExceptionObject($exception2);

        $repository->find($this->aggregateId);
    }

    public function testFindingAggregateIfNoEventsInStore()
    {
        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($this->aggregateId)
            ->willReturn($this->aggregate);

        $this->store
            ->expects($this->once())
            ->method('find')
            ->with($this->aggregateId)
            ->willReturn([])
        ;

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->uow);

        $aggregate = $repository->find($this->aggregateId);

        $this->assertNull($aggregate);
    }


    public function testFindingAggregate()
    {
        $aggregate = new EventSourcedAggregateRootStub($this->aggregateId);

        $event1 = new EventStub($this->aggregateId);
        $event2 = new EventStub($this->aggregateId);
        $event3 = new EventStub($this->aggregateId);
        $event4 = new EventStub($this->aggregateId);

        $this->store
            ->expects($this->once())
            ->method('find')
            ->with($this->aggregateId)
            ->willReturn([$event1, $event2, $event3, $event4])
        ;

        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($this->aggregateId)
            ->willReturn($aggregate);

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->uow);

        $result = $repository->find($this->aggregateId);

        $this->assertSame($aggregate, $result);
    }

    public function testAddingNonEventSourcedAggregate()
    {
        $exception = new Domain\Exception\AggregateNotSupported($this->nonEventSourcedAggregate);
        $this->expectExceptionObject($exception);

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->uow);
        $repository->add($this->nonEventSourcedAggregate);
    }


    public function testAddingAggregate()
    {
        $this->aggregateId
            ->expects($this->once())
            ->method('equals')
            ->with($this->equalTo($this->aggregateId))
            ->willReturn(true);

        $aggregate = new EventSourcedAggregateRootStub($this->aggregateId);

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->uow);
        $repository->add($aggregate);

        $this->assertTrue($this->uow->has($aggregate));
    }
}

class EventStub implements Domain\Event
{
    private $id;

    public function __construct(Domain\AggregateRootId $id)
    {
        $this->id = $id;
    }

    public function aggregateRootId() : Domain\AggregateRootId
    {
        return $this->id;
    }
}

class EventSourcedAggregateRootStub extends Domain\EventSourced\AggregateRoot
{
    public function applyStubEvent(EventStub $event)
    {
    }
}
