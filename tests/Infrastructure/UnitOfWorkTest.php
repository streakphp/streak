<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Infrastructure;

use PHPUnit\Framework\TestCase;
use Streak\Domain;


/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class UnitOfWorkTest extends TestCase
{
    /**
     * @var Domain\EventStore|\PHPUnit_Framework_MockObject_MockObject
     */
    private $store;

    /**
     * @var Domain\EventSourced\AggregateRoot|\PHPUnit_Framework_MockObject_MockObject
     */
    private $aggregate1;

    /**
     * @var Domain\AggregateRootId|\PHPUnit_Framework_MockObject_MockObject
     */
    private $aggregateId1;

    /**
     * @var Domain\EventSourced\AggregateRoot|\PHPUnit_Framework_MockObject_MockObject
     */
    private $aggregate2;

    /**
     * @var Domain\AggregateRootId|\PHPUnit_Framework_MockObject_MockObject
     */
    private $aggregateId2;

    public function setUp()
    {
        $this->store = $this->getMockBuilder(Domain\EventStore::class)->getMockForAbstractClass();

        $this->aggregateId1 = $this->getMockBuilder(Domain\AggregateRootId::class)->setMockClassName('aggregate_id_1')->getMockForAbstractClass();
        $this->aggregate1 = $this->getMockBuilder(Domain\EventSourced\AggregateRoot::class)->setConstructorArgs([$this->aggregateId1])->getMockForAbstractClass();

        $this->aggregateId2 = $this->getMockBuilder(Domain\AggregateRootId::class)->setMockClassName('aggregate_id_2')->getMockForAbstractClass();
        $this->aggregate2 = $this->getMockBuilder(Domain\EventSourced\AggregateRoot::class)->setConstructorArgs([$this->aggregateId2])->getMockForAbstractClass();
    }

    public function testObject()
    {
        $this->aggregateId1
            ->expects($this->at(0))
            ->method('equals')
            ->with($this->aggregateId1)
            ->willReturn(true)
        ;

        $this->aggregateId1
            ->expects($this->at(1))
            ->method('equals')
            ->with($this->aggregateId2)
            ->willReturn(false)
        ;

        $this->aggregateId1
            ->expects($this->at(2))
            ->method('equals')
            ->with($this->aggregateId2)
            ->willReturn(false)
        ;

        $this->aggregateId1
            ->expects($this->at(3))
            ->method('equals')
            ->with($this->aggregateId1)
            ->willReturn(true)
        ;

        $this->aggregateId1
            ->expects($this->at(4))
            ->method('equals')
            ->with($this->aggregateId2)
            ->willReturn(false)
        ;

        $this->aggregateId1
            ->expects($this->at(5))
            ->method('equals')
            ->with($this->aggregateId2)
            ->willReturn(false)
        ;

        $this->aggregateId1
            ->expects($this->at(6))
            ->method('equals')
            ->with($this->aggregateId1)
            ->willReturn(true)
        ;

        $this->aggregateId1
            ->expects($this->at(7))
            ->method('equals')
            ->with($this->aggregateId2)
            ->willReturn(false)
        ;

        $this->aggregateId2
            ->expects($this->at(0))
            ->method('equals')
            ->with($this->aggregateId2)
            ->willReturn(true)
        ;

        $this->aggregateId2
            ->expects($this->at(1))
            ->method('equals')
            ->with($this->aggregateId2)
            ->willReturn(true)
        ;

        $uow = new UnitOfWork($this->store);

        $this->assertEquals(0, $uow->count());
        $this->assertFalse($uow->has($this->aggregate1));
        $this->assertFalse($uow->has($this->aggregate2));

        $uow->add($this->aggregate1);

        $this->assertEquals(1, $uow->count());
        $this->assertTrue($uow->has($this->aggregate1));
        $this->assertFalse($uow->has($this->aggregate2));

        $uow->add($this->aggregate2);

        $this->assertEquals(2, $uow->count());
        $this->assertTrue($uow->has($this->aggregate1));
        $this->assertTrue($uow->has($this->aggregate2));

        $uow->remove($this->aggregate2);

        $this->assertEquals(1, $uow->count());
        $this->assertTrue($uow->has($this->aggregate1));
        $this->assertFalse($uow->has($this->aggregate2));

        $this->store
            ->expects($this->once())
            ->method('add')
            ->with()
        ;

        $uow->commit();

        $this->assertEquals(0, $uow->count());
        $this->assertFalse($uow->has($this->aggregate1));
        $this->assertFalse($uow->has($this->aggregate2));
    }
}
