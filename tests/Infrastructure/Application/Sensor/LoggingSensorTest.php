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

namespace Streak\Infrastructure\Application\Sensor;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Streak\Application\Sensor;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Application\Sensor\LoggingSensor
 */
class LoggingSensorTest extends TestCase
{
    private Sensor $sensor;

    private LoggerInterface $logger;

    private Sensor\Id $sensorId;

    private Event $event;

    protected function setUp(): void
    {
        $this->sensor = $this->getMockBuilder(Sensor::class)->setMockClassName('SensorMock001')->getMockForAbstractClass();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        $this->sensorId = $this->getMockBuilder(Sensor\Id::class)->getMockForAbstractClass();
        $this->event = $this->getMockBuilder(Event::class)->setMockClassName('EventMock001')->getMockForAbstractClass();
    }

    public function testObject(): void
    {
        $sensor = new LoggingSensor($this->sensor, $this->logger);

        $this->logger
            ->expects(self::never())
            ->method('debug')
        ;

        $this->sensor
            ->expects(self::exactly(2))
            ->method('process')
            ->with($this->event)
            ->willReturnOnConsecutiveCalls(true, false)
        ;

        $sensor->process($this->event);
        $sensor->process($this->event);

        $this->sensor
            ->expects(self::once())
            ->method('sensorId')
            ->willReturn($this->sensorId)
        ;

        self::assertSame($this->sensorId, $sensor->sensorId());

        $this->sensor
            ->expects(self::once())
            ->method('producerId')
            ->willReturn($this->sensorId)
        ;

        self::assertSame($this->sensorId, $sensor->producerId());

        $this->sensor
            ->expects(self::once())
            ->method('events')
            ->willReturn([$this->event])
        ;

        self::assertSame([$this->event], $sensor->events());
    }

    public function testExceptionOnEvent(): void
    {
        $sensor = new LoggingSensor($this->sensor, $this->logger);

        $exception = new \Exception('Exception test message.');
        $this->sensor
            ->expects(self::once())
            ->method('process')
            ->with($this->event)
            ->willThrowException($exception)
        ;

        $this->logger
            ->expects(self::once())
            ->method('debug')
            ->with(self::isType('string'), [
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
