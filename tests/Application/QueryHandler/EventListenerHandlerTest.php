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

namespace Streak\Application\QueryHandler;

use PHPUnit\Framework\TestCase;
use Streak\Application\QueryHandler\EventListenerHandlerTest\QueryHandlingListener;
use Streak\Domain\Event;
use Streak\Domain\Event\Subscription\Exception\ListenerNotFound;
use Streak\Application\Event\Listener\Subscription\Repository;
use Streak\Domain\Exception\QueryNotSupported;
use Streak\Domain\Query;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Application\QueryHandler\EventListenerHandler
 */
class EventListenerHandlerTest extends TestCase
{
    private Repository $repository;

    private Query $query;

    private \Streak\Application\Event\Listener\Subscription $subscription;

    private \Streak\Application\Event\Listener $eventListener;

    private QueryHandlingListener $eventListenerQueryHandler;

    private Query\EventListenerQuery $eventListenerQuery;

    private \Streak\Application\Event\Listener\Id $eventListenerId;

    protected function setUp(): void
    {
        $this->repository = $this->getMockBuilder(Repository::class)->getMockForAbstractClass();
        $this->query = $this->getMockBuilder(Query::class)->getMockForAbstractClass();
        $this->subscription = $this->getMockBuilder(\Streak\Application\Event\Listener\Subscription::class)->getMockForAbstractClass();
        $this->eventListener = $this->getMockBuilder(\Streak\Application\Event\Listener::class)->getMockForAbstractClass();
        $this->eventListenerQueryHandler = $this->getMockBuilder(QueryHandlingListener::class)->getMock();
        $this->eventListenerQuery = $this->getMockBuilder(Query\EventListenerQuery::class)->getMockForAbstractClass();
        $this->eventListenerId = $this->getMockBuilder(\Streak\Application\Event\Listener\Id::class)->getMockForAbstractClass();
    }

    public function testQueryNotSupported(): void
    {
        $this->expectExceptionObject(new QueryNotSupported($this->query));

        $handler = new EventListenerHandler($this->repository);
        $handler->handleQuery($this->query);
    }

    public function testListenerNotFound(): void
    {
        $this->expectExceptionObject(new ListenerNotFound($this->eventListenerId));

        $this->eventListenerQuery
            ->expects(self::once())
            ->method('listenerId')
            ->willReturn($this->eventListenerId)
        ;

        $this->repository
            ->expects(self::once())
            ->method('find')
            ->with($this->eventListenerId)
            ->willReturn(null)
        ;

        $handler = new EventListenerHandler($this->repository);
        $handler->handleQuery($this->eventListenerQuery);
    }

    public function testListenerNotAQueryHandler(): void
    {
        $this->expectExceptionObject(new QueryNotSupported($this->eventListenerQuery));

        $this->eventListenerQuery
            ->expects(self::once())
            ->method('listenerId')
            ->willReturn($this->eventListenerId)
        ;
        $this->repository
            ->expects(self::once())
            ->method('find')
            ->with($this->eventListenerId)
            ->willReturn($this->subscription)
        ;
        $this->subscription
            ->expects(self::once())
            ->method('listener')
            ->willReturn($this->eventListener)
        ;

        $handler = new EventListenerHandler($this->repository);
        $handler->handleQuery($this->eventListenerQuery);
    }

    public function testQueryHandlingEventListener(): void
    {
        $this->eventListenerQuery
            ->expects(self::once())
            ->method('listenerId')
            ->willReturn($this->eventListenerId)
        ;
        $this->repository
            ->expects(self::once())
            ->method('find')
            ->with($this->eventListenerId)
            ->willReturn($this->subscription)
        ;
        $this->subscription
            ->expects(self::once())
            ->method('listener')
            ->willReturn($this->eventListenerQueryHandler)
        ;
        $this->eventListenerQueryHandler
            ->expects(self::once())
            ->method('handleQuery')
            ->with($this->eventListenerQuery)
        ;

        $handler = new EventListenerHandler($this->repository);
        $handler->handleQuery($this->eventListenerQuery);
    }
}

namespace Streak\Application\QueryHandler\EventListenerHandlerTest;

use Streak\Domain\Event;
use Streak\Domain\QueryHandler;

abstract class QueryHandlingListener implements QueryHandler, \Streak\Application\Event\Listener
{
}
