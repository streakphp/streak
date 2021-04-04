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

namespace Streak\Infrastructure\RabbitMQ;

use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Application\Sensor;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\RabbitMQ\SensorConsumer
 */
class SensorConsumerTest extends TestCase
{
    private const ACK = true;
    private const NACK = false;

    /**
     * @var Sensor\Factory|MockObject
     */
    private $factory;

    /**
     * @var Sensor|MockObject
     */
    private $sensor;

    protected function setUp() : void
    {
        $this->factory = $this->getMockBuilder(Sensor\Factory::class)->getMockForAbstractClass();
        $this->sensor = $this->getMockBuilder(Sensor::class)->getMockForAbstractClass();
    }

    public function testAckWithJsonMessage()
    {
        $consumer = new SensorConsumer($this->factory);

        $message = new AMQPMessage('{"hello": "world"}');

        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with()
            ->willReturn($this->sensor)
        ;

        $this->sensor
            ->expects($this->once())
            ->method('process')
            ->with(['hello' => 'world'])
        ;

        $result = $consumer->execute($message);

        $this->assertSame(self::ACK, $result);
    }

    public function testAckWithTextMessage()
    {
        $consumer = new SensorConsumer($this->factory);

        $message = new AMQPMessage('Hello world!');

        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with()
            ->willReturn($this->sensor)
        ;

        $this->sensor
            ->expects($this->once())
            ->method('process')
            ->with('Hello world!')
        ;

        $result = $consumer->execute($message);

        $this->assertSame(self::ACK, $result);
    }

    public function testNackWithJsonMessage()
    {
        $consumer = new SensorConsumer($this->factory);

        $message = new AMQPMessage('{"hello": "world"}');

        $this->factory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->sensor)
        ;

        $this->sensor
            ->expects($this->once())
            ->method('process')
            ->with(['hello' => 'world'])
            ->willThrowException(new \RuntimeException())
        ;

        $result = $consumer->execute($message);

        $this->assertSame(self::NACK, $result);
    }

    public function testNackWithTextMessage()
    {
        $consumer = new SensorConsumer($this->factory);

        $message = new AMQPMessage('Hello world!');

        $this->factory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->sensor)
        ;

        $this->sensor
            ->expects($this->once())
            ->method('process')
            ->with('Hello world!')
            ->willThrowException(new \RuntimeException())
        ;

        $result = $consumer->execute($message);

        $this->assertSame(self::NACK, $result);
    }
}
