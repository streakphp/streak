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

use PHPUnit\Framework\TestCase;
use Streak\Domain;
use Streak\Domain\Event;
use Streak\Infrastructure;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\AggregateRoot\Repository\EventSourcedRepository
 */
class EventSourcedRepositoryTest extends TestCase
{
    /**
     * @var Domain\AggregateRoot\Factory|\PHPUnit_Framework_MockObject_MockObject
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
    private $nonEventSourcedAggregateRoot;

    /**
     * @var Event\Sourced\AggregateRoot|\PHPUnit_Framework_MockObject_MockObject
     */
    private $aggregateRoot;

    /**
     * @var Domain\AggregateRoot\Id|\PHPUnit_Framework_MockObject_MockObject
     */
    private $aggregateRootId;

    /**
     * @var Domain\Event|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event1;

    /**
     * @var Domain\Event|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event2;

    /**
     * @var Domain\Event|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event3;

    /**
     * @var Domain\Event|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event4;

    public function setUp()
    {
        $this->factory = $this->getMockBuilder(Domain\AggregateRoot\Factory::class)->getMockForAbstractClass();
        $this->store = $this->getMockBuilder(Domain\EventStore::class)->getMockForAbstractClass();
        $this->uow = new Infrastructure\UnitOfWork($this->store);

        $this->nonEventSourcedAggregateRoot = $this->getMockBuilder(Domain\AggregateRoot::class)->getMockForAbstractClass();
        $this->aggregateRoot = $this->getMockBuilder(Domain\Event\Sourced\AggregateRoot::class)->getMockForAbstractClass();

        $this->aggregateRootId = $this->getMockBuilder(Domain\AggregateRoot\Id::class)->getMockForAbstractClass();

        $this->event1 = $this->getMockBuilder(Domain\Event::class)->getMockForAbstractClass();
        $this->event2 = $this->getMockBuilder(Domain\Event::class)->getMockForAbstractClass();
        $this->event3 = $this->getMockBuilder(Domain\Event::class)->getMockForAbstractClass();
        $this->event4 = $this->getMockBuilder(Domain\Event::class)->getMockForAbstractClass();
    }

    public function testFindingNonEventSourcedAggregate()
    {
        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($this->aggregateRootId)
            ->willReturn($this->nonEventSourcedAggregateRoot)
        ;

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->uow);

        $exception = new Domain\Exception\AggregateNotSupported($this->nonEventSourcedAggregateRoot);
        $this->expectExceptionObject($exception);

        $repository->find($this->aggregateRootId);
    }

    public function testFindingAggregateNotCompatibleWithStore()
    {
        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($this->aggregateRootId)
            ->willReturn($this->nonEventSourcedAggregateRoot)
        ;

        $exception1 = new Domain\Exception\InvalidAggregateIdGiven($this->aggregateRootId);

        $this->store
            ->expects($this->never())
            ->method('find')
            ->with($this->aggregateRootId)
            ->willThrowException($exception1)
        ;

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->uow);

        $exception2 = new Domain\Exception\AggregateNotSupported($this->nonEventSourcedAggregateRoot, $exception1);
        $this->expectExceptionObject($exception2);

        $repository->find($this->aggregateRootId);
    }

    public function testFindingAggregateIfNoEventsInStore()
    {
        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($this->aggregateRootId)
            ->willReturn($this->aggregateRoot)
        ;

        $this->store
            ->expects($this->once())
            ->method('find')
            ->with($this->aggregateRootId)
            ->willReturn([])
        ;

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->uow);

        $aggregate = $repository->find($this->aggregateRootId);

        $this->assertNull($aggregate);
    }

    public function testFindingAggregate()
    {
        $events = [$this->event1, $this->event2, $this->event3, $this->event4];

        $this->store
            ->expects($this->once())
            ->method('find')
            ->with($this->aggregateRootId)
            ->willReturn($events)
        ;

        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($this->aggregateRootId)
            ->willReturn($this->aggregateRoot)
        ;

        $this->aggregateRoot
            ->expects($this->once())
            ->method('replay')
            ->with(...$events)
        ;

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->uow);

        $aggregate = $repository->find($this->aggregateRootId);

        $this->assertSame($this->aggregateRoot, $aggregate);
    }

    public function testAddingNonEventSourcedAggregate()
    {
        $exception = new Domain\Exception\AggregateNotSupported($this->nonEventSourcedAggregateRoot);
        $this->expectExceptionObject($exception);

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->uow);
        $repository->add($this->nonEventSourcedAggregateRoot);
    }

    public function testAddingAggregate()
    {
        $this->aggregateRoot
            ->expects($this->once())
            ->method('equals')
            ->with($this->equalTo($this->aggregateRoot))
            ->willReturn(true)
        ;

        $repository = new EventSourcedRepository($this->factory, $this->store, $this->uow);
        $repository->add($this->aggregateRoot);

        $this->assertTrue($this->uow->has($this->aggregateRoot));
    }
}
