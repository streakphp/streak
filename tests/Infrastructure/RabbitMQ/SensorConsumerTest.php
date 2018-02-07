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

    protected function setUp()
    {
        $this->factory = $this->getMockBuilder(Sensor\Factory::class)->getMockForAbstractClass();
        $this->sensor = $this->getMockBuilder(Sensor::class)->getMockForAbstractClass();
    }

    public function testAck()
    {
        $consumer = new SensorConsumer($this->factory);

        $message = new AMQPMessage();

        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with()
            ->willReturn($this->sensor)
        ;

        $this->sensor
            ->expects($this->once())
            ->method('process')
            ->with($message)
        ;

        $result = $consumer->execute($message);

        $this->assertSame(self::ACK, $result);
    }

    public function testNack()
    {
        $consumer = new SensorConsumer($this->factory);

        $message = new AMQPMessage();
        $exception = new \RuntimeException();

        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with()
            ->willReturn($this->sensor)
        ;

        $this->sensor
            ->expects($this->once())
            ->method('process')
            ->with($message)
            ->willThrowException($exception)
        ;

        $result = $consumer->execute($message);

        $this->assertSame(self::NACK, $result);
    }
}
