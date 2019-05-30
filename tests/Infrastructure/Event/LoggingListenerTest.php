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

namespace Streak\Infrastructure\Event;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Streak\Application\Exception\QueryNotSupported;
use Streak\Application\Query;
use Streak\Application\QueryHandler;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Event\LoggingListener
 */
class LoggingListenerTest extends TestCase
{
    /**
     * @var Event\Listener|MockObject
     */
    private $listener1;

    /**
     * @var Event\Listener|Event\Listener\Replayable|Event\Listener\Completable|Event\Listener\Resettable|Event\Filterer|QueryHandler|MockObject
     */
    private $listener2;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var Listener\Id|MockObject
     */
    private $listenerId;

    /**
     * @var Event|MockObject
     */
    private $event;

    /**
     * @var Event\Stream|MockObject
     */
    private $stream1;

    /**
     * @var Event\Stream|MockObject
     */
    private $stream2;

    /**
     * @var Query|MockObject
     */
    private $query;

    protected function setUp()
    {
        $this->listener1 = $this->getMockBuilder(Listener::class)->setMethods(['replay', 'reset', 'completed'])->setMockClassName('ListenerMock001')->getMockForAbstractClass();
        $this->listener2 = $this->getMockBuilder([Event\Listener::class, Event\Listener\Replayable::class, Event\Listener\Completable::class, Event\Listener\Resettable::class, Event\Filterer::class, QueryHandler::class])->setMockClassName('ListenerMock002')->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        $this->listenerId = $this->getMockBuilder(Listener\Id::class)->getMockForAbstractClass();
        $this->event = $this->getMockBuilder(Event::class)->setMockClassName('EventMock001')->getMockForAbstractClass();
        $this->stream1 = $this->getMockBuilder(Event\Stream::class)->setMockClassName('stream1')->getMockForAbstractClass();
        $this->stream2 = $this->getMockBuilder(Event\Stream::class)->setMockClassName('stream2')->getMockForAbstractClass();
        $this->query = $this->getMockBuilder(Query::class)->getMockForAbstractClass();
    }

    public function testObject()
    {
        $listener = new LoggingListener($this->listener1, $this->logger);

        $this->logger
            ->expects($this->never())
            ->method('debug')
        ;
        $this->listener1
            ->expects($this->never())
            ->method('replay')
        ;
        $this->listener1
            ->expects($this->never())
            ->method('reset')
        ;
        $this->listener1
            ->expects($this->never())
            ->method('completed')
        ;
        $listener->replay($this->stream1);
        $listener->reset();
        $this->assertFalse($listener->completed());

        $this->stream1
            ->expects($this->never())
            ->method($this->anything())
        ;

        $filtered = $listener->filter($this->stream1);

        $this->assertSame($this->stream1, $filtered);

        $listener = new LoggingListener($this->listener2, $this->logger);

        $this->logger
            ->expects($this->never())
            ->method('debug')
        ;

        $this->listener2
            ->expects($this->exactly(2))
            ->method('completed')
            ->willReturnOnConsecutiveCalls(true, false)
        ;

        $this->assertTrue($listener->completed());
        $this->assertFalse($listener->completed());

        $this->listener2
            ->expects($this->exactly(2))
            ->method('on')
            ->with($this->event)
            ->willReturnOnConsecutiveCalls(true, false)
        ;

        $this->assertTrue($listener->on($this->event));
        $this->assertFalse($listener->on($this->event));

        $this->listener2
            ->expects($this->once())
            ->method('replay')
            ->with($this->stream1)
        ;

        $listener->replay($this->stream1);

        $this->listener2
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->listenerId)
        ;

        $this->assertSame($this->listenerId, $listener->listenerId());
        $this->assertSame($this->listenerId, $listener->id());

        $listener->reset();
    }

    public function testExceptionOnEvent()
    {
        $listener = new LoggingListener($this->listener2, $this->logger);

        $exception = new \Exception('Exception test message.');
        $this->listener2
            ->expects($this->once())
            ->method('on')
            ->with($this->event)
            ->willThrowException($exception)
        ;

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with($this->isType('string'), [
                'listener' => 'ListenerMock002',
                'class' => 'Exception',
                'message' => 'Exception test message.',
                'event' => 'EventMock001',
                'exception' => $exception,
            ])
        ;

        $this->expectExceptionObject($exception);

        $listener->on($this->event);
    }

    public function testExceptionWhenReplayingEvents()
    {
        $listener = new LoggingListener($this->listener2, $this->logger);

        $exception = new \Exception('Exception test message.');
        $this->listener2
            ->expects($this->once())
            ->method('replay')
            ->with($this->stream1)
            ->willThrowException($exception)
        ;

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with($this->isType('string'), [
                'listener' => 'ListenerMock002',
                'class' => 'Exception',
                'message' => 'Exception test message.',
                'exception' => $exception,
            ])
        ;

        $this->expectExceptionObject($exception);

        $listener->replay($this->stream1);
    }

    public function testExceptionWhenResettingListener()
    {
        $listener = new LoggingListener($this->listener2, $this->logger);

        $exception = new \Exception('Exception test message #2');
        $this->listener2
            ->expects($this->once())
            ->method('reset')
            ->with()
            ->willThrowException($exception)
        ;

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with($this->isType('string'), [
                'listener' => 'ListenerMock002',
                'class' => 'Exception',
                'message' => 'Exception test message #2',
                'exception' => $exception,
            ])
        ;

        $this->expectExceptionObject($exception);

        $listener->reset();
    }

    public function testFiltering()
    {
        $listener = new LoggingListener($this->listener2, $this->logger);

        $this->listener2
            ->expects($this->once())
            ->method('filter')
            ->with($this->stream1)
            ->willReturn($this->stream2)
        ;

        $filtered = $listener->filter($this->stream1);

        $this->assertSame($this->stream2, $filtered);
    }

    public function testQueringNotSupported()
    {
        $this->expectExceptionObject(new QueryNotSupported($this->query));

        $listener = new LoggingListener($this->listener1, $this->logger);

        $listener->handleQuery($this->query);
    }

    public function testQuering()
    {
        $listener = new LoggingListener($this->listener2, $this->logger);

        $this->listener2
            ->expects($this->once())
            ->method('handleQuery')
            ->with($this->query)
            ->willReturn('result')
        ;

        $result = $listener->handleQuery($this->query);

        $this->assertSame('result', $result);
    }
}
