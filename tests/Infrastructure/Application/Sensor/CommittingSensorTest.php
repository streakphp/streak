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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Application\Sensor;
use Streak\Domain\Event;
use Streak\Infrastructure\Domain\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Application\Sensor\CommittingSensor
 */
class CommittingSensorTest extends TestCase
{
    private Sensor|MockObject $sensor;

    private UnitOfWork|MockObject $uow;

    private Sensor\Id|MockObject $sensorId;

    private Event|MockObject $event1;
    private Event|MockObject $event2;

    protected function setUp(): void
    {
        $this->sensor = $this->getMockBuilder(Sensor::class)->setMockClassName('SensorMock002')->getMockForAbstractClass();
        $this->uow = $this->getMockBuilder(UnitOfWork::class)->getMockForAbstractClass();
        $this->sensorId = $this->getMockBuilder(Sensor\Id::class)->getMockForAbstractClass();
        $this->event1 = $this->getMockBuilder(Event::class)->setMockClassName('EventMock001')->getMockForAbstractClass();
        $this->event2 = $this->getMockBuilder(Event::class)->setMockClassName('EventMock002')->getMockForAbstractClass();
    }

    public function testSensor(): void
    {
        $sensor = new CommittingSensor($this->sensor, $this->uow);

        $this->sensor
            ->expects(self::once())
            ->method('id')
            ->with()
            ->willReturn($this->sensorId)
        ;

        self::assertSame($this->sensorId, $sensor->id());

        $this->sensor
            ->expects(self::once())
            ->method('events')
            ->with()
            ->willReturn([$this->event1, $this->event2])
        ;

        self::assertSame([$this->event1, $this->event2], $sensor->events());

        $messages = ['message 1', 'message 2'];

        $this->sensor
            ->expects(self::once())
            ->method('process')
            ->with(...$messages)
        ;

        $generator = (function () {
            yield $this->event1;
            yield $this->event2;
        })();

        $this->uow
            ->expects(self::once())
            ->method('commit')
            ->with()
            ->willReturn($generator)
        ;

        self::assertTrue($generator->valid());

        $sensor->process(...$messages);

        self::assertFalse($generator->valid());
    }

    public function testException(): void
    {
        $sensor = new CommittingSensor($this->sensor, $this->uow);

        $messages = ['message 1', 'message 2'];

        $this->sensor
            ->expects(self::once())
            ->method('process')
            ->with(...$messages)
            ->willThrowException($exception = new \RuntimeException('test'))
        ;

        $this->uow
            ->expects(self::never())
            ->method('commit')
        ;

        $this->expectExceptionObject($exception);

        $sensor->process(...$messages);
    }
}
