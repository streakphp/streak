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

namespace Streak\Infrastructure\Saga;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Streak\Application\Saga;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Saga\LoggingSaga
 */
class LoggingSagaTest extends TestCase
{
    /**
     * @var Saga|MockObject
     */
    private $saga;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var Saga\Id|MockObject
     */
    private $sagaId;

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
        $this->saga = $this->getMockBuilder(Saga::class)->setMockClassName('SagaMock001')->getMockForAbstractClass();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        $this->sagaId = $this->getMockBuilder(Saga\Id::class)->getMockForAbstractClass();
        $this->event = $this->getMockBuilder(Event::class)->setMockClassName('EventMock001')->getMockForAbstractClass();
        $this->stream = $this->getMockBuilder(Event\Stream::class)->getMockForAbstractClass();
    }

    public function testObject()
    {
        $saga = new LoggingSaga($this->saga, $this->logger);

        $this->logger
            ->expects($this->never())
            ->method('debug')
        ;

        $this->saga
            ->expects($this->exactly(2))
            ->method('completed')
            ->willReturnOnConsecutiveCalls(true, false)
        ;

        $this->assertTrue($saga->completed());
        $this->assertFalse($saga->completed());

        $this->saga
            ->expects($this->once())
            ->method('id')
            ->willReturn($this->sagaId)
        ;

        $this->assertSame($this->sagaId, $saga->id());

        $this->saga
            ->expects($this->exactly(2))
            ->method('on')
            ->with($this->event)
            ->willReturnOnConsecutiveCalls(true, false)
        ;

        $this->assertTrue($saga->on($this->event));
        $this->assertFalse($saga->on($this->event));

        $this->saga
            ->expects($this->once())
            ->method('replay')
            ->with($this->stream)
        ;

        $saga->replay($this->stream);

        $this->saga
            ->expects($this->once())
            ->method('sagaId')
            ->willReturn($this->sagaId)
        ;

        $this->assertSame($this->sagaId, $saga->sagaId());
    }

    public function testExceptionOnEvent()
    {
        $saga = new LoggingSaga($this->saga, $this->logger);

        $exception = new \Exception('Exception test message.');
        $this->saga
            ->expects($this->once())
            ->method('on')
            ->with($this->event)
            ->willThrowException($exception)
        ;

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with($this->isType('string'), [
                'saga' => 'SagaMock001',
                'class' => 'Exception',
                'message' => 'Exception test message.',
                'event' => 'EventMock001',
                'exception' => $exception,
            ])
        ;

        $this->expectExceptionObject($exception);

        $saga->on($this->event);
    }

    public function testExceptionWhenReplayingEvents()
    {
        $saga = new LoggingSaga($this->saga, $this->logger);

        $exception = new \Exception('Exception test message.');
        $this->saga
            ->expects($this->once())
            ->method('replay')
            ->with($this->stream)
            ->willThrowException($exception)
        ;

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with($this->isType('string'), [
                'saga' => 'SagaMock001',
                'class' => 'Exception',
                'message' => 'Exception test message.',
                'exception' => $exception,
            ])
        ;

        $this->expectExceptionObject($exception);

        $saga->replay($this->stream);
    }
}
