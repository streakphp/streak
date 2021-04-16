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
     * @var \PHPUnit_Framework_MockObject_MockObject|QueryHandler
     */
    private $handler1;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|QueryHandler
     */
    private $handler2;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|QueryHandler
     */
    private $handler3;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Query
     */
    private $query1;

    protected function setUp(): void
    {
        $this->handler1 = $this->getMockBuilder(QueryHandler::class)->setMockClassName('query_handler1')->getMockForAbstractClass();
        $this->handler2 = $this->getMockBuilder(QueryHandler::class)->setMockClassName('query_handler2')->getMockForAbstractClass();
        $this->handler3 = $this->getMockBuilder(QueryHandler::class)->setMockClassName('query_handler3')->getMockForAbstractClass();

        $this->query1 = $this->getMockBuilder(Query::class)->setMockClassName('query1')->getMockForAbstractClass();
    }

    public function testAlreadyRegisteredHandler(): void
    {
        $handler = new CompositeQueryHandler();

        $handler->registerHandler($this->handler1);
        $handler->registerHandler($this->handler2);
        $handler->registerHandler($this->handler3);

        $exception = new QueryHandlerAlreadyRegistered($this->handler1);

        $this->expectExceptionObject($exception);

        $handler->registerHandler($this->handler1);
    }

    public function testQueryHandling(): void
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
            ->expects(self::once())
            ->method('handleQuery')
            ->with($this->query1)
            ->willThrowException($exception)
        ;
        $this->handler2
            ->expects(self::once())
            ->method('handleQuery')
            ->with($this->query1)
            ->willReturn($expected)
        ;
        $this->handler3
            ->expects(self::never())
            ->method('handleQuery')
        ;

        $actual = $handler->handleQuery($this->query1);

        self::assertSame($expected, $actual);
    }

    public function testNoHandlers(): void
    {
        $handler = new CompositeQueryHandler();

        $exception = new QueryNotSupported($this->query1);

        $this->expectExceptionObject($exception);

        $handler->handleQuery($this->query1);
    }

    public function testNoHandlerForQuery(): void
    {
        $handler = new CompositeQueryHandler(
            $this->handler1,
            $this->handler2,
            $this->handler1, // should be filtered out
            $this->handler2  // should be filtered out
        );

        $exception = new QueryNotSupported($this->query1);

        $this->handler1
            ->expects(self::once())
            ->method('handleQuery')
            ->with($this->query1)
            ->willThrowException($exception)
        ;
        $this->handler2
            ->expects(self::once())
            ->method('handleQuery')
            ->with($this->query1)
            ->willThrowException($exception)
        ;

        $exception = new QueryNotSupported($this->query1);

        $this->expectExceptionObject($exception);

        $handler->handleQuery($this->query1);
    }
}
