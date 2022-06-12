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

namespace Streak\Infrastructure\Interfaces\RabbitMQ;

use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Application\Sensor;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Interfaces\RabbitMQ\SensorConsumer
 */
class SensorConsumerTest extends TestCase
{
    private const ACK = true;
    private const NACK = false;

    private Sensor\Factory|MockObject $factory;

    private Sensor|MockObject $sensor;

    protected function setUp(): void
    {
        $this->factory = $this->getMockBuilder(Sensor\Factory::class)->getMockForAbstractClass();
        $this->sensor = $this->getMockBuilder(Sensor::class)->getMockForAbstractClass();
    }

    public function testAckWithJsonMessage(): void
    {
        $consumer = new SensorConsumer($this->factory);

        $message = new AMQPMessage('{"hello": "world"}');

        $this->factory
            ->expects(self::once())
            ->method('create')
            ->with()
            ->willReturn($this->sensor)
        ;

        $this->sensor
            ->expects(self::once())
            ->method('process')
            ->with(['hello' => 'world'])
        ;

        $result = $consumer->execute($message);

        self::assertSame(self::ACK, $result);
    }

    public function testAckWithTextMessage(): void
    {
        $consumer = new SensorConsumer($this->factory);

        $message = new AMQPMessage('Hello world!');

        $this->factory
            ->expects(self::once())
            ->method('create')
            ->with()
            ->willReturn($this->sensor)
        ;

        $this->sensor
            ->expects(self::once())
            ->method('process')
            ->with('Hello world!')
        ;

        $result = $consumer->execute($message);

        self::assertSame(self::ACK, $result);
    }

    public function testNackWithJsonMessage(): void
    {
        $consumer = new SensorConsumer($this->factory);

        $message = new AMQPMessage('{"hello": "world"}');

        $this->factory
            ->expects(self::once())
            ->method('create')
            ->willReturn($this->sensor)
        ;

        $this->sensor
            ->expects(self::once())
            ->method('process')
            ->with(['hello' => 'world'])
            ->willThrowException(new \RuntimeException())
        ;

        $result = $consumer->execute($message);

        self::assertSame(self::NACK, $result);
    }

    public function testNackWithTextMessage(): void
    {
        $consumer = new SensorConsumer($this->factory);

        $message = new AMQPMessage('Hello world!');

        $this->factory
            ->expects(self::once())
            ->method('create')
            ->willReturn($this->sensor)
        ;

        $this->sensor
            ->expects(self::once())
            ->method('process')
            ->with('Hello world!')
            ->willThrowException(new \RuntimeException())
        ;

        $result = $consumer->execute($message);

        self::assertSame(self::NACK, $result);
    }
}
