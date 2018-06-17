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
use Streak\Domain\Event;
use Streak\Domain\Id;

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
    private $listener;

    /**
     * @var Event\Listener|MockObject
     */
    private $rawListener;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var Id|MockObject
     */
    private $listenerId;

    /**
     * @var Event|MockObject
     */
    private $event;

    /**
     * @var Event\Stream|MockObject
     */
    private $stream;

    protected function setUp()
    {
        $this->listener = $this->getMockBuilder([Event\Listener::class, Event\Replayable::class, Event\Completable::class])->setMockClassName('ListenerMock001')->getMock();
        $this->rawListener = $this->getMockBuilder(Event\Listener::class)->setMockClassName('ListenerMock002')->setMethods(['completed', 'replay'])->getMockForAbstractClass();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        $this->listenerId = $this->getMockBuilder(Id::class)->getMockForAbstractClass();
        $this->event = $this->getMockBuilder(Event::class)->setMockClassName('EventMock001')->getMockForAbstractClass();
        $this->stream = $this->getMockBuilder(Event\Stream::class)->getMockForAbstractClass();
    }

    public function testObject()
    {
        $listener = new LoggingListener($this->rawListener, $this->logger);

        $this->logger
            ->expects($this->never())
            ->method('debug')
        ;

        $this->rawListener
            ->expects($this->never())
            ->method('completed')
        ;
        $this->assertFalse($listener->completed());

        $this->rawListener
            ->expects($this->never())
            ->method('replay')
        ;
        $listener->replay($this->stream);

        $listener = new LoggingListener($this->listener, $this->logger);

        $this->logger
            ->expects($this->never())
            ->method('debug')
        ;

        $this->listener
            ->expects($this->exactly(2))
            ->method('completed')
            ->willReturnOnConsecutiveCalls(true, false)
        ;

        $this->assertTrue($listener->completed());
        $this->assertFalse($listener->completed());

        $this->listener
            ->expects($this->exactly(2))
            ->method('on')
            ->with($this->event)
            ->willReturnOnConsecutiveCalls(true, false)
        ;

        $this->assertTrue($listener->on($this->event));
        $this->assertFalse($listener->on($this->event));

        $this->listener
            ->expects($this->once())
            ->method('replay')
            ->with($this->stream)
        ;

        $listener->replay($this->stream);

        $this->listener
            ->expects($this->once())
            ->method('id')
            ->willReturn($this->listenerId)
        ;

        $this->assertSame($this->listenerId, $listener->id());
    }

    public function testExceptionOnEvent()
    {
        $listener = new LoggingListener($this->listener, $this->logger);

        $exception = new \Exception('Exception test message.');
        $this->listener
            ->expects($this->once())
            ->method('on')
            ->with($this->event)
            ->willThrowException($exception)
        ;

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with($this->isType('string'), [
                'listener' => 'ListenerMock001',
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
        $listener = new LoggingListener($this->listener, $this->logger);

        $exception = new \Exception('Exception test message.');
        $this->listener
            ->expects($this->once())
            ->method('replay')
            ->with($this->stream)
            ->willThrowException($exception)
        ;

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with($this->isType('string'), [
                'listener' => 'ListenerMock001',
                'class' => 'Exception',
                'message' => 'Exception test message.',
                'exception' => $exception,
            ])
        ;

        $this->expectExceptionObject($exception);

        $listener->replay($this->stream);
    }
}
