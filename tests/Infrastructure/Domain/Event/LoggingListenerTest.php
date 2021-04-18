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

namespace Streak\Infrastructure\Domain\Event;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Domain\Exception\QueryNotSupported;
use Streak\Domain\Id\UUID;
use Streak\Domain\Query;
use Streak\Infrastructure\Domain\Event\LoggingListenerTest\ListenerWithAllPossibleFeatures;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\Event\LoggingListener
 */
class LoggingListenerTest extends TestCase
{
    private Event\Listener $listener1;

    private ListenerWithAllPossibleFeatures $listener2;

    private LoggerInterface $logger;

    private Listener\Id $listenerId;

    private Event\Envelope $event;

    private Event\Stream $stream1;
    private Event\Stream $stream2;

    private Query $query;

    private Listener\State $state1;
    private Listener\State $state2;

    protected function setUp(): void
    {
        $this->listener1 = $this->getMockBuilder(Listener::class)->addMethods(['replay', 'reset', 'completed'])->setMockClassName('ListenerMock001')->getMockForAbstractClass();
        $this->listener2 = $this->getMockBuilder(ListenerWithAllPossibleFeatures::class)->setMockClassName('ListenerMock002')->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        $this->listenerId = $this->getMockBuilder(Listener\Id::class)->getMockForAbstractClass();
        $this->event = Event\Envelope::new($this->getMockBuilder(Event::class)->setMockClassName('EventMock001')->getMockForAbstractClass(), UUID::random());
        $this->stream1 = $this->getMockBuilder(Event\Stream::class)->setMockClassName('stream1')->getMockForAbstractClass();
        $this->stream2 = $this->getMockBuilder(Event\Stream::class)->setMockClassName('stream2')->getMockForAbstractClass();
        $this->query = $this->getMockBuilder(Query::class)->getMockForAbstractClass();
        $this->state1 = $this->getMockBuilder(Listener\State::class)->getMockForAbstractClass();
        $this->state2 = $this->getMockBuilder(Listener\State::class)->getMockForAbstractClass();
    }

    public function testObject(): void
    {
        $listener = new LoggingListener($this->listener1, $this->logger);

        $this->logger
            ->expects(self::never())
            ->method('debug')
        ;
        $this->listener1
            ->expects(self::never())
            ->method('reset')
        ;
        $this->listener1
            ->expects(self::never())
            ->method('completed')
        ;
        $listener->reset();
        self::assertFalse($listener->completed());

        $this->stream1
            ->expects(self::never())
            ->method(self::anything())
        ;

        $filtered = $listener->filter($this->stream1);

        self::assertSame($this->stream1, $filtered);

        $this->listener2
            ->expects(self::never())
            ->method('fromState')
        ;
        $this->listener2
            ->expects(self::never())
            ->method('toState')
        ;
        $listener->fromState($this->state1);

        $state = $listener->toState($this->state1);

        self::assertSame($this->state1, $state);

        $listener = new LoggingListener($this->listener2, $this->logger);

        $this->logger
            ->expects(self::never())
            ->method('debug')
        ;

        $this->listener2
            ->expects(self::exactly(2))
            ->method('completed')
            ->willReturnOnConsecutiveCalls(true, false)
        ;

        self::assertTrue($listener->completed());
        self::assertFalse($listener->completed());

        $this->listener2
            ->expects(self::exactly(2))
            ->method('on')
            ->with($this->event)
            ->willReturnOnConsecutiveCalls(true, false)
        ;

        self::assertTrue($listener->on($this->event));
        self::assertFalse($listener->on($this->event));

        $this->listener2
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->listenerId)
        ;

        self::assertSame($this->listenerId, $listener->listenerId());
        self::assertSame($this->listenerId, $listener->id());

        $listener->reset();
    }

    public function testExceptionOnEvent(): void
    {
        $listener = new LoggingListener($this->listener2, $this->logger);

        $exception = new \Exception('Exception test message.');
        $this->listener2
            ->expects(self::once())
            ->method('on')
            ->with($this->event)
            ->willThrowException($exception)
        ;

        $this->logger
            ->expects(self::once())
            ->method('debug')
            ->with(self::isType('string'), [
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

    public function testExceptionWhenResettingListener(): void
    {
        $listener = new LoggingListener($this->listener2, $this->logger);

        $exception = new \Exception('Exception test message #2');
        $this->listener2
            ->expects(self::once())
            ->method('reset')
            ->with()
            ->willThrowException($exception)
        ;

        $this->logger
            ->expects(self::once())
            ->method('debug')
            ->with(self::isType('string'), [
                'listener' => 'ListenerMock002',
                'class' => 'Exception',
                'message' => 'Exception test message #2',
                'exception' => $exception,
            ])
        ;

        $this->expectExceptionObject($exception);

        $listener->reset();
    }

    public function testFiltering(): void
    {
        $listener = new LoggingListener($this->listener2, $this->logger);

        $this->listener2
            ->expects(self::once())
            ->method('filter')
            ->with($this->stream1)
            ->willReturn($this->stream2)
        ;

        $filtered = $listener->filter($this->stream1);

        self::assertSame($this->stream2, $filtered);
    }

    public function testQueringNotSupported(): void
    {
        $this->expectExceptionObject(new QueryNotSupported($this->query));

        $listener = new LoggingListener($this->listener1, $this->logger);

        $listener->handleQuery($this->query);
    }

    public function testQuering(): void
    {
        $listener = new LoggingListener($this->listener2, $this->logger);

        $this->listener2
            ->expects(self::once())
            ->method('handleQuery')
            ->with($this->query)
            ->willReturn('result')
        ;

        $result = $listener->handleQuery($this->query);

        self::assertSame('result', $result);
    }

    public function testState(): void
    {
        $listener = new LoggingListener($this->listener2, $this->logger);

        $this->listener2
            ->expects(self::once())
            ->method('fromState')
            ->with(self::identicalTo($this->state1))
        ;

        $listener->fromState($this->state1);

        $this->listener2
            ->expects(self::once())
            ->method('toState')
            ->with(self::identicalTo($this->state2))
            ->willReturn($this->state1)
        ;

        $state = $listener->toState($this->state2);

        self::assertSame($this->state1, $state);
    }
}

namespace Streak\Infrastructure\Domain\Event\LoggingListenerTest;

use Streak\Domain\Event;
use Streak\Domain\QueryHandler;

abstract class ListenerWithAllPossibleFeatures implements Event\Listener, Event\Listener\Completable, Event\Listener\Resettable, Event\Filterer, QueryHandler, Event\Listener\Stateful
{
}
