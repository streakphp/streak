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

namespace Streak\Infrastructure\QueryHandler;

use PHPUnit\Framework\TestCase;
use Streak\Application\Exception\QueryHandlerAlreadyRegistered;
use Streak\Application\Exception\QueryNotSupported;
use Streak\Application\Query;
use Streak\Application\QueryHandler;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\QueryHandler\CompositeQueryHandler
 */
class CompositeQueryHandlerTest extends TestCase
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

    public function setUp()
    {
        $this->handler1 = $this->getMockBuilder(QueryHandler::class)->setMockClassName('query_handler1')->getMockForAbstractClass();
        $this->handler2 = $this->getMockBuilder(QueryHandler::class)->setMockClassName('query_handler2')->getMockForAbstractClass();
        $this->handler3 = $this->getMockBuilder(QueryHandler::class)->setMockClassName('query_handler3')->getMockForAbstractClass();

        $this->query1 = $this->getMockBuilder(Query::class)->setMockClassName('query1')->getMockForAbstractClass();
    }

    public function testAlreadyRegisteredHandler()
    {
        $handler = new CompositeQueryHandler();

        $handler->registerHandler($this->handler1);
        $handler->registerHandler($this->handler2);
        $handler->registerHandler($this->handler3);

        $exception = new QueryHandlerAlreadyRegistered($this->handler1);

        $this->expectExceptionObject($exception);

        $handler->registerHandler($this->handler1);
    }

    public function testQueryHandling()
    {
        $expected = new \stdClass();

        $handler = new CompositeQueryHandler(
            $this->handler1,
            $this->handler2,
            $this->handler3,
            $this->handler1, // should be filtered out
            $this->handler2, // should be filtered out
            $this->handler3  // should be filtered out
        );

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

        $actual = $handler->handleQuery($this->query1);

        $this->assertSame($expected, $actual);
    }

    public function testNoHandlers()
    {
        $handler = new CompositeQueryHandler();

        $exception = new QueryNotSupported($this->query1);

        $this->expectExceptionObject($exception);

        $handler->handleQuery($this->query1);
    }

    public function testNoHandlerForQuery()
    {
        $handler = new CompositeQueryHandler(
            $this->handler1,
            $this->handler2,
            $this->handler1, // should be filtered out
            $this->handler2  // should be filtered out
        );

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

        $exception = new QueryNotSupported($this->query1);

        $this->expectExceptionObject($exception);

        $handler->handleQuery($this->query1);
    }
}
