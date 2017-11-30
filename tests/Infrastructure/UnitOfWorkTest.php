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
 *
 * @covers \Streak\Infrastructure\UnitOfWork
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
    private $aggregateRoot1;

    /**
     * @var Domain\EventSourced\AggregateRoot|\PHPUnit_Framework_MockObject_MockObject
     */
    private $aggregateRoot2;

    public function setUp()
    {
        $this->store = $this->getMockBuilder(Domain\EventStore::class)->getMockForAbstractClass();

        $this->aggregateRoot1 = $this->getMockBuilder(Domain\EventSourced\AggregateRoot::class)->getMockForAbstractClass();
        $this->aggregateRoot2 = $this->getMockBuilder(Domain\EventSourced\AggregateRoot::class)->getMockForAbstractClass();
    }

    public function testObject()
    {
        $this->aggregateRoot1
            ->expects($this->at(0))
            ->method('equals')
            ->with($this->aggregateRoot1)
            ->willReturn(true)
        ;

        $this->aggregateRoot1
            ->expects($this->at(1))
            ->method('equals')
            ->with($this->aggregateRoot2)
            ->willReturn(false)
        ;

        $this->aggregateRoot1
            ->expects($this->at(2))
            ->method('equals')
            ->with($this->aggregateRoot2)
            ->willReturn(false)
        ;

        $this->aggregateRoot1
            ->expects($this->at(3))
            ->method('equals')
            ->with($this->aggregateRoot1)
            ->willReturn(true)
        ;

        $this->aggregateRoot1
            ->expects($this->at(4))
            ->method('equals')
            ->with($this->aggregateRoot2)
            ->willReturn(false)
        ;

        $this->aggregateRoot1
            ->expects($this->at(5))
            ->method('equals')
            ->with($this->aggregateRoot2)
            ->willReturn(false)
        ;

        $this->aggregateRoot1
            ->expects($this->at(6))
            ->method('equals')
            ->with($this->aggregateRoot1)
            ->willReturn(true)
        ;

        $this->aggregateRoot1
            ->expects($this->at(7))
            ->method('equals')
            ->with($this->aggregateRoot2)
            ->willReturn(false)
        ;

        $this->aggregateRoot2
            ->expects($this->at(0))
            ->method('equals')
            ->with($this->aggregateRoot2)
            ->willReturn(true)
        ;

        $this->aggregateRoot2
            ->expects($this->at(1))
            ->method('equals')
            ->with($this->aggregateRoot2)
            ->willReturn(true)
        ;

        $uow = new UnitOfWork($this->store);

        $this->assertEquals(0, $uow->count());
        $this->assertFalse($uow->has($this->aggregateRoot1));
        $this->assertFalse($uow->has($this->aggregateRoot2));

        $uow->add($this->aggregateRoot1);

        $this->assertEquals(1, $uow->count());
        $this->assertTrue($uow->has($this->aggregateRoot1));
        $this->assertFalse($uow->has($this->aggregateRoot2));

        $uow->add($this->aggregateRoot2);

        $this->assertEquals(2, $uow->count());
        $this->assertTrue($uow->has($this->aggregateRoot1));
        $this->assertTrue($uow->has($this->aggregateRoot2));

        $uow->remove($this->aggregateRoot2);

        $this->assertEquals(1, $uow->count());
        $this->assertTrue($uow->has($this->aggregateRoot1));
        $this->assertFalse($uow->has($this->aggregateRoot2));

        $this->store
            ->expects($this->once())
            ->method('add')
            ->with()
        ;

        $uow->commit();

        $this->assertEquals(0, $uow->count());
        $this->assertFalse($uow->has($this->aggregateRoot1));
        $this->assertFalse($uow->has($this->aggregateRoot2));
    }
}
