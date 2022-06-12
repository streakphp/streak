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

namespace Streak\Infrastructure\Application\QueryBus;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Exception\QueryNotSupported;
use Streak\Domain\Query;
use Streak\Domain\QueryHandler;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Application\QueryBus\SynchronousQueryBus
 */
class SynchronousQueryBusTest extends TestCase
{
    private QueryHandler|MockObject $handler1;
    private QueryHandler|MockObject $handler2;
    private QueryHandler|MockObject $handler3;

    private Query|MockObject $query1;

    protected function setUp(): void
    {
        $this->handler1 = $this->getMockBuilder(QueryHandler::class)->setMockClassName('query_handler1')->getMockForAbstractClass();
        $this->handler2 = $this->getMockBuilder(QueryHandler::class)->setMockClassName('query_handler2')->getMockForAbstractClass();
        $this->handler3 = $this->getMockBuilder(QueryHandler::class)->setMockClassName('query_handler3')->getMockForAbstractClass();

        $this->query1 = $this->getMockBuilder(Query::class)->setMockClassName('query1')->getMockForAbstractClass();
    }

    public function testQueryHandling(): void
    {
        $bus = new SynchronousQueryBus();

        $bus->register($this->handler1);
        $bus->register($this->handler1); // repeated handler does not really change anything, so we allow them
        $bus->register($this->handler2);
        $bus->register($this->handler2);
        $bus->register($this->handler3);

        $exception = new QueryNotSupported($this->query1);

        $this->handler1
            ->expects(self::exactly(2))
            ->method('handleQuery')
            ->with($this->query1)
            ->willThrowException($exception)
        ;
        $this->handler2
            ->expects(self::once())
            ->method('handleQuery')
            ->with($this->query1)
            ->willReturn($expected = new \stdClass())
        ;
        $this->handler3
            ->expects(self::never())
            ->method('handleQuery')
        ;

        $actual = $bus->dispatch($this->query1);

        self::assertSame($expected, $actual);
    }

    public function testNoHandlers(): void
    {
        $bus = new SynchronousQueryBus();

        $exception = new QueryNotSupported($this->query1);

        $this->expectExceptionObject($exception);

        $bus->dispatch($this->query1);
    }

    public function testNoHandlerForQuery(): void
    {
        $bus = new SynchronousQueryBus();

        $bus->register($this->handler1);
        $bus->register($this->handler2);

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

        $this->expectExceptionObject($exception);

        $bus->dispatch($this->query1);
    }
}
