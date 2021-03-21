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

namespace Streak\Infrastructure\QueryBus;

use PHPUnit\Framework\TestCase;
use Streak\Application\Exception\QueryHandlerAlreadyRegistered;
use Streak\Application\Exception\QueryNotSupported;
use Streak\Application\Query;
use Streak\Application\QueryHandler;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\QueryBus\SynchronousQueryBus
 */
class SynchronousQueryBusTest extends TestCase
{
    /**
     * @var QueryHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $handler1;

    /**
     * @var QueryHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $handler2;

    /**
     * @var QueryHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $handler3;

    /**
     * @var Query|\PHPUnit_Framework_MockObject_MockObject
     */
    private $query1;

    public function setUp() : void
    {
        $this->handler1 = $this->getMockBuilder(QueryHandler::class)->setMockClassName('query_handler1')->getMockForAbstractClass();
        $this->handler2 = $this->getMockBuilder(QueryHandler::class)->setMockClassName('query_handler2')->getMockForAbstractClass();
        $this->handler3 = $this->getMockBuilder(QueryHandler::class)->setMockClassName('query_handler3')->getMockForAbstractClass();

        $this->query1 = $this->getMockBuilder(Query::class)->setMockClassName('query1')->getMockForAbstractClass();
    }

    public function testAlreadyRegisteredHandler()
    {
        $bus = new SynchronousQueryBus();

        $bus->register($this->handler1);
        $bus->register($this->handler2);
        $bus->register($this->handler3);

        $exception = new QueryHandlerAlreadyRegistered($this->handler1);

        $this->expectExceptionObject($exception);

        $bus->register($this->handler1);
    }

    public function testQueryHandling()
    {
        $expected = new \stdClass();
        $bus = new SynchronousQueryBus();

        $bus->register($this->handler1);
        $bus->register($this->handler2);
        $bus->register($this->handler3);

        $exception = new QueryNotSupported($this->query1);

        $this->handler1
            ->expects($this->once())
            ->method('handleQuery')
            ->with($this->query1)
            ->willThrowException($exception)
        ;
        $this->handler2
            ->expects($this->once())
            ->method('handleQuery')
            ->with($this->query1)
            ->willReturn($expected)
        ;
        $this->handler3
            ->expects($this->never())
            ->method('handleQuery')
        ;

        $actual = $bus->dispatch($this->query1);

        $this->assertSame($expected, $actual);
    }

    public function testNoHandlers()
    {
        $bus = new SynchronousQueryBus();

        $exception = new QueryNotSupported($this->query1);

        $this->expectExceptionObject($exception);

        $bus->dispatch($this->query1);
    }

    public function testNoHandlerForQuery()
    {
        $bus = new SynchronousQueryBus();

        $bus->register($this->handler1);
        $bus->register($this->handler2);

        $exception = new QueryNotSupported($this->query1);

        $this->handler1
            ->expects($this->once())
            ->method('handleQuery')
            ->with($this->query1)
            ->willThrowException($exception)
        ;
        $this->handler2
            ->expects($this->once())
            ->method('handleQuery')
            ->with($this->query1)
            ->willThrowException($exception)
        ;

        $this->expectExceptionObject($exception);

        $bus->dispatch($this->query1);
    }
}
