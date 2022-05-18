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

namespace Streak\Infrastructure\Domain\UnitOfWork;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Infrastructure\Domain\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\UnitOfWork\CompositeUnitOfWork
 */
class CompositeUnitOfWorkTest extends TestCase
{
    private UnitOfWork|MockObject $uow1;
    private UnitOfWork|MockObject $uow2;
    private UnitOfWork|MockObject $uow3;

    protected function setUp(): void
    {
        $this->uow1 = $this->getMockBuilder(UnitOfWork::class)->setMockClassName('uow1')->getMockForAbstractClass();
        $this->uow2 = $this->getMockBuilder(UnitOfWork::class)->setMockClassName('uow2')->getMockForAbstractClass();
        $this->uow3 = $this->getMockBuilder(UnitOfWork::class)->setMockClassName('uow3')->getMockForAbstractClass();
    }

    public function testObject(): void
    {
        $object1 = new \stdClass();

        $exception1 = new UnitOfWork\Exception\ObjectNotSupported($object1);

        $uow = new CompositeUnitOfWork($this->uow1, $this->uow2, $this->uow3);

        $this->uow1
            ->expects(self::at(0))
            ->method('has')
            ->with($object1)
            ->willReturn(false)
        ;
        $this->uow2
            ->expects(self::at(0))
            ->method('has')
            ->with($object1)
            ->willReturn(false)
        ;
        $this->uow3
            ->expects(self::at(0))
            ->method('has')
            ->with($object1)
            ->willReturn(false)
        ;

        self::assertFalse($uow->has($object1));

        $this->uow1
            ->expects(self::once())
            ->method('add')
            ->with($object1)
            ->willThrowException($exception1)
        ;
        $this->uow2
            ->expects(self::once())
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

        self::assertTrue($uow->has($object1));

        $this->uow1
            ->expects(self::once())
            ->method('remove')
            ->with($object1)
        ;
        $this->uow2
            ->expects(self::once())
            ->method('remove')
            ->with($object1)
        ;
        $this->uow3
            ->expects(self::once())
            ->method('remove')
            ->with($object1)
        ;

        $uow->remove($object1);

        $this->uow1
            ->expects(self::once())
            ->method('count')
            ->willReturn(0)
        ;
        $this->uow2
            ->expects(self::once())
            ->method('count')
            ->willReturn(1)
        ;
        $this->uow3
            ->expects(self::once())
            ->method('count')
            ->willReturn(0)
        ;

        self::assertSame(1, $uow->count());

        $this->uow1
            ->expects(self::once())
            ->method('uncommitted')
            ->willReturn([])
        ;
        $this->uow2
            ->expects(self::once())
            ->method('uncommitted')
            ->willReturn([$object1])
        ;
        $this->uow3
            ->expects(self::once())
            ->method('uncommitted')
            ->willReturn([])
        ;

        self::assertSame([$object1], $uow->uncommitted());

        $this->uow1
            ->expects(self::once())
            ->method('commit')
            ->willReturnCallback(function () {
                yield from [];
            })
        ;
        $this->uow2
            ->expects(self::once())
            ->method('commit')
            ->willReturnCallback(function () use ($object1) {
                yield from [$object1];
            })
        ;
        $this->uow3
            ->expects(self::once())
            ->method('commit')
            ->willReturnCallback(function () {
                yield from [];
            })
        ;

        $committed = $uow->commit();
        $committed = iterator_to_array($committed);

        self::assertSame([$object1], $committed);

        $this->uow1
            ->expects(self::once())
            ->method('clear')
        ;
        $this->uow2
            ->expects(self::once())
            ->method('clear')
        ;
        $this->uow3
            ->expects(self::once())
            ->method('clear')
        ;

        $uow->clear();
    }

    public function testObjectNotSupported(): void
    {
        $object1 = new \stdClass();

        $exception1 = new UnitOfWork\Exception\ObjectNotSupported($object1);
        $exception2 = new UnitOfWork\Exception\ObjectNotSupported($object1);
        $exception3 = new UnitOfWork\Exception\ObjectNotSupported($object1);

        $this->expectExceptionObject(new UnitOfWork\Exception\ObjectNotSupported($object1));

        $uow = new CompositeUnitOfWork($this->uow1, $this->uow2, $this->uow3);

        $this->uow1
            ->expects(self::once())
            ->method('add')
            ->with($object1)
            ->willThrowException($exception1)
        ;
        $this->uow2
            ->expects(self::once())
            ->method('add')
            ->with($object1)
            ->willThrowException($exception2)
        ;
        $this->uow3
            ->expects(self::once())
            ->method('add')
            ->with($object1)
            ->willThrowException($exception3)
        ;

        $uow->add($object1);
    }
}
