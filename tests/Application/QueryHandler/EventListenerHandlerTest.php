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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Application\Exception\QueryNotSupported;
use Streak\Application\Query;
use Streak\Application\QueryHandler;
use Streak\Domain\Event;
use Streak\Domain\Event\Subscription\Exception\ListenerNotFound;
use Streak\Domain\Event\Subscription\Repository;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Application\QueryHandler\EventListenerHandler
 */
class EventListenerHandlerTest extends TestCase
{
    /**
     * @var Repository|MockObject
     */
    private $repository;

    /**
     * @var Query|MockObject
     */
    private $query;

    /**
     * @var Event\Subscription|MockObject
     */
    private $subscription;

    /**
     * @var Event\Listener|MockObject
     */
    private $eventListener;

    /**
     * @var Event\Listener|QueryHandler|MockObject
     */
    private $eventListenerQueryHandler;

    /**
     * @var Query\EventListenerQuery|MockObject
     */
    private $eventListenerQuery;

    /**
     * @var Event\Listener\Id|MockObject
     */
    private $eventListenerId;

    protected function setUp()
    {
        $this->repository = $this->getMockBuilder(Repository::class)->getMockForAbstractClass();
        $this->query = $this->getMockBuilder(Query::class)->getMockForAbstractClass();
        $this->subscription = $this->getMockBuilder(Event\Subscription::class)->getMockForAbstractClass();
        $this->eventListener = $this->getMockBuilder(Event\Listener::class)->getMockForAbstractClass();
        $this->eventListenerId = $this->getMockBuilder(Event\Listener\Id::class)->getMockForAbstractClass();
        $this->eventListenerQuery = $this->getMockBuilder(Query\EventListenerQuery::class)->getMockForAbstractClass();
        $this->eventListenerQueryHandler = $this->getMockBuilder([Event\Listener::class, QueryHandler::class])->getMock();
    }

    public function testQueryNotSupported()
    {
        $this->expectExceptionObject(new QueryNotSupported($this->query));

        $handler = new EventListenerHandler($this->repository);
        $handler->handleQuery($this->query);
    }

    public function testListenerNotFound()
    {
        $this->expectExceptionObject(new ListenerNotFound($this->eventListenerId));

        $this->eventListenerQuery
            ->expects($this->once())
            ->method('listenerId')
            ->willReturn($this->eventListenerId)
        ;

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with($this->eventListenerId)
            ->willReturn(null)
        ;

        $handler = new EventListenerHandler($this->repository);
        $handler->handleQuery($this->eventListenerQuery);
    }

    public function testListenerNotAQueryHandler()
    {
        $this->expectExceptionObject(new QueryNotSupported($this->eventListenerQuery));

        $this->eventListenerQuery
            ->expects($this->once())
            ->method('listenerId')
            ->willReturn($this->eventListenerId)
        ;
        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with($this->eventListenerId)
            ->willReturn($this->subscription)
        ;
        $this->subscription
            ->expects($this->once())
            ->method('listener')
            ->willReturn($this->eventListener)
        ;

        $handler = new EventListenerHandler($this->repository);
        $handler->handleQuery($this->eventListenerQuery);
    }

    public function testQueryHandlingEventListener()
    {
        $this->eventListenerQuery
            ->expects($this->once())
            ->method('listenerId')
            ->willReturn($this->eventListenerId)
        ;
        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with($this->eventListenerId)
            ->willReturn($this->subscription)
        ;
        $this->subscription
            ->expects($this->once())
            ->method('listener')
            ->willReturn($this->eventListenerQueryHandler)
        ;
        $this->eventListenerQueryHandler
            ->expects($this->once())
            ->method('handleQuery')
            ->with($this->eventListenerQuery)
        ;

        $handler = new EventListenerHandler($this->repository);
        $handler->handleQuery($this->eventListenerQuery);
    }
}
