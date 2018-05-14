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

namespace Streak\Infrastructure\Sensor;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Streak\Application\Sensor;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Sensor\LoggingSensor
 */
class LoggingSensorTest extends TestCase
{
    /**
     * @var Sensor|MockObject
     */
    private $sensor;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var Sensor\Id|MockObject
     */
    private $sensorId;

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
        $this->sensor = $this->getMockBuilder(Sensor::class)->setMockClassName('SensorMock001')->getMockForAbstractClass();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        $this->sensorId = $this->getMockBuilder(Sensor\Id::class)->getMockForAbstractClass();
        $this->event = $this->getMockBuilder(Event::class)->setMockClassName('EventMock001')->getMockForAbstractClass();
        $this->stream = $this->getMockBuilder(Event\Stream::class)->getMockForAbstractClass();
    }

    public function testObject()
    {
        $sensor = new LoggingSensor($this->sensor, $this->logger);

        $this->logger
            ->expects($this->never())
            ->method('debug')
        ;

        $this->sensor
            ->expects($this->exactly(2))
            ->method('process')
            ->with($this->event)
            ->willReturnOnConsecutiveCalls(true, false)
        ;

        $sensor->process($this->event);
        $sensor->process($this->event);

        $this->sensor
            ->expects($this->once())
            ->method('sensorId')
            ->willReturn($this->sensorId)
        ;

        $this->assertSame($this->sensorId, $sensor->sensorId());

        $this->sensor
            ->expects($this->once())
            ->method('producerId')
            ->willReturn($this->sensorId)
        ;

        $this->assertSame($this->sensorId, $sensor->producerId());

        $this->sensor
            ->expects($this->once())
            ->method('events')
            ->willReturn([$this->event])
        ;

        $this->assertSame([$this->event], $sensor->events());
    }

    public function testExceptionOnEvent()
    {
        $sensor = new LoggingSensor($this->sensor, $this->logger);

        $exception = new \Exception('Exception test message.');
        $this->sensor
            ->expects($this->once())
            ->method('process')
            ->with($this->event)
            ->willThrowException($exception)
        ;

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with($this->isType('string'), [
                'sensor' => 'SensorMock001',
                'class' => 'Exception',
                'message' => 'Exception test message.',
                'exception' => $exception,
            ])
        ;

        $this->expectExceptionObject($exception);

        $sensor->process($this->event);
    }
}
