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

namespace Streak\Infrastructure\UnitOfWork;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Infrastructure\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\UnitOfWork\CompositeUnitOfWork
 */
class CompositeUnitOfWorkTest extends TestCase
{
    /**
     * @var UnitOfWork|MockObject
     */
    private $uow1;

    /**
     * @var UnitOfWork|MockObject
     */
    private $uow2;

    /**
     * @var UnitOfWork|MockObject
     */
    private $uow3;

    protected function setUp()
    {
        $this->uow1 = $this->getMockBuilder(UnitOfWork::class)->setMockClassName('uow1')->getMockForAbstractClass();
        $this->uow2 = $this->getMockBuilder(UnitOfWork::class)->setMockClassName('uow2')->getMockForAbstractClass();
        $this->uow3 = $this->getMockBuilder(UnitOfWork::class)->setMockClassName('uow3')->getMockForAbstractClass();
    }

    public function testObject()
    {
        $object1 = new \stdClass();

        $exception1 = new UnitOfWork\Exception\ObjectNotSupported($object1);

        $uow = new CompositeUnitOfWork($this->uow1, $this->uow2, $this->uow3);

        $this->uow1
            ->expects($this->at(0))
            ->method('has')
            ->with($object1)
            ->willReturn(false)
        ;
        $this->uow2
            ->expects($this->at(0))
            ->method('has')
            ->with($object1)
            ->willReturn(false)
        ;
        $this->uow3
            ->expects($this->at(0))
            ->method('has')
            ->with($object1)
            ->willReturn(false)
        ;

        $this->assertFalse($uow->has($object1));

        $this->uow1
            ->expects($this->once())
            ->method('add')
            ->with($object1)
            ->willThrowException($exception1)
        ;
        $this->uow2
            ->expects($this->once())
            ->method('add')
            ->with($object1)
        ;

        $uow->add($object1);

        $this->uow1
            ->method('has')
            ->with($object1)
            ->willReturn(true)
        ;
        $this->uow2
            ->method('has')
            ->with($object1)
            ->willReturn(true)
        ;

        $this->assertTrue($uow->has($object1));

        $this->uow1
            ->expects($this->once())
            ->method('remove')
            ->with($object1)
        ;
        $this->uow2
            ->expects($this->once())
            ->method('remove')
            ->with($object1)
        ;
        $this->uow3
            ->expects($this->once())
            ->method('remove')
            ->with($object1)
        ;

        $uow->remove($object1);

        $this->uow1
            ->expects($this->once())
            ->method('count')
            ->willReturn(0)
        ;
        $this->uow2
            ->expects($this->once())
            ->method('count')
            ->willReturn(1)
        ;
        $this->uow3
            ->expects($this->once())
            ->method('count')
            ->willReturn(0)
        ;

        $this->assertSame(1, $uow->count());

        $this->uow1
            ->expects($this->once())
            ->method('uncommitted')
            ->willReturn([])
        ;
        $this->uow2
            ->expects($this->once())
            ->method('uncommitted')
            ->willReturn([$object1])
        ;
        $this->uow3
            ->expects($this->once())
            ->method('uncommitted')
            ->willReturn([])
        ;

        $this->assertSame([$object1], $uow->uncommitted());

        $this->uow1
            ->expects($this->once())
            ->method('commit')
            ->willReturnCallback(function () { yield from []; })
        ;
        $this->uow2
            ->expects($this->once())
            ->method('commit')
            ->willReturnCallback(function () use ($object1) { yield from [$object1]; })
        ;
        $this->uow3
            ->expects($this->once())
            ->method('commit')
            ->willReturnCallback(function () { yield from []; })
        ;

        $committed = $uow->commit();
        $committed = iterator_to_array($committed);

        $this->assertSame([$object1], $committed);

        $this->uow1
            ->expects($this->once())
            ->method('clear')
        ;
        $this->uow2
            ->expects($this->once())
            ->method('clear')
        ;
        $this->uow3
            ->expects($this->once())
            ->method('clear')
        ;

        $uow->clear();
    }

    public function testObjectNotSupported()
    {
        $object1 = new \stdClass();

        $exception1 = new UnitOfWork\Exception\ObjectNotSupported($object1);
        $exception2 = new UnitOfWork\Exception\ObjectNotSupported($object1);
        $exception3 = new UnitOfWork\Exception\ObjectNotSupported($object1);

        $this->expectExceptionObject(new UnitOfWork\Exception\ObjectNotSupported($object1));

        $uow = new CompositeUnitOfWork($this->uow1, $this->uow2, $this->uow3);

        $this->uow1
            ->expects($this->once())
            ->method('add')
            ->with($object1)
            ->willThrowException($exception1)
        ;
        $this->uow2
            ->expects($this->once())
            ->method('add')
            ->with($object1)
            ->willThrowException($exception2)
        ;
        $this->uow3
            ->expects($this->once())
            ->method('add')
            ->with($object1)
            ->willThrowException($exception3)
        ;

        $uow->add($object1);
    }
}
