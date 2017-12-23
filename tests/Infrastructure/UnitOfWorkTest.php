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

namespace Streak\Infrastructure;

use PHPUnit\Framework\TestCase;
use Streak\Domain;
use Streak\Domain\Event;

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
     * @var Event\Sourced\AggregateRoot|\PHPUnit_Framework_MockObject_MockObject
     */
    private $object1;

    /**
     * @var Event\Sourced\AggregateRoot|\PHPUnit_Framework_MockObject_MockObject
     */
    private $object2;

    public function setUp()
    {
        $this->store = $this->getMockBuilder(Domain\EventStore::class)->getMockForAbstractClass();

        $this->object1 = $this->getMockBuilder(Domain\Event\Sourced::class)->getMockForAbstractClass();
        $this->object2 = $this->getMockBuilder(Domain\Event\Sourced::class)->getMockForAbstractClass();
    }

    public function testObject()
    {
        $this->object1
            ->expects($this->at(0))
            ->method('equals')
            ->with($this->object1)
            ->willReturn(true)
        ;

        $this->object1
            ->expects($this->at(1))
            ->method('equals')
            ->with($this->object2)
            ->willReturn(false)
        ;

        $this->object1
            ->expects($this->at(2))
            ->method('equals')
            ->with($this->object2)
            ->willReturn(false)
        ;

        $this->object1
            ->expects($this->at(3))
            ->method('equals')
            ->with($this->object1)
            ->willReturn(true)
        ;

        $this->object1
            ->expects($this->at(4))
            ->method('equals')
            ->with($this->object2)
            ->willReturn(false)
        ;

        $this->object1
            ->expects($this->at(5))
            ->method('equals')
            ->with($this->object2)
            ->willReturn(false)
        ;

        $this->object1
            ->expects($this->at(6))
            ->method('equals')
            ->with($this->object1)
            ->willReturn(true)
        ;

        $this->object1
            ->expects($this->at(7))
            ->method('equals')
            ->with($this->object2)
            ->willReturn(false)
        ;

        $this->object2
            ->expects($this->at(0))
            ->method('equals')
            ->with($this->object2)
            ->willReturn(true)
        ;

        $this->object2
            ->expects($this->at(1))
            ->method('equals')
            ->with($this->object2)
            ->willReturn(true)
        ;

        $uow = new UnitOfWork($this->store);

        $this->assertEquals(0, $uow->count());
        $this->assertFalse($uow->has($this->object1));
        $this->assertFalse($uow->has($this->object2));

        $uow->add($this->object1);

        $this->assertEquals(1, $uow->count());
        $this->assertTrue($uow->has($this->object1));
        $this->assertFalse($uow->has($this->object2));

        $uow->add($this->object2);

        $this->assertEquals(2, $uow->count());
        $this->assertTrue($uow->has($this->object1));
        $this->assertTrue($uow->has($this->object2));

        $uow->remove($this->object2);

        $this->assertEquals(1, $uow->count());
        $this->assertTrue($uow->has($this->object1));
        $this->assertFalse($uow->has($this->object2));

        $this->store
            ->expects($this->once())
            ->method('add')
            ->with()
        ;

        $uow->commit();

        $this->assertEquals(0, $uow->count());
        $this->assertFalse($uow->has($this->object1));
        $this->assertFalse($uow->has($this->object2));
    }
}
