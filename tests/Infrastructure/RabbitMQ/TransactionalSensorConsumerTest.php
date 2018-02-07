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

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Infrastructure\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\RabbitMQ\TransactionalSensorConsumer
 */
class TransactionalSensorConsumerTest extends TestCase
{
    private const ACK = true;
    private const NACK = false;

    /**
     * @var ConsumerInterface|MockObject
     */
    private $consumer;

    /**
     * @var UnitOfWork|MockObject
     */
    private $uow;

    protected function setUp()
    {
        $this->consumer = $this->getMockBuilder(ConsumerInterface::class)->getMockForAbstractClass();
        $this->uow = $this->getMockBuilder(UnitOfWork::class)->disableOriginalConstructor()->getMock();
    }

    public function testAck()
    {
        $consumer = new TransactionalSensorConsumer($this->consumer, $this->uow);

        $message = new AMQPMessage();

        $this->uow
            ->expects($this->at(0))
            ->method('clear')
            ->with()
        ;

        $this->consumer
            ->expects($this->once())
            ->method('execute')
            ->with($message)
            ->willReturn(self::ACK)
        ;

        $this->uow
            ->expects($this->at(1))
            ->method('commit')
            ->with()
        ;

        $this->uow
            ->expects($this->at(0))
            ->method('clear')
            ->with()
        ;

        $result = $consumer->execute($message);

        $this->assertSame(self::ACK, $result);
    }

    public function testNack()
    {
        $consumer = new TransactionalSensorConsumer($this->consumer, $this->uow);

        $message = new AMQPMessage();

        $this->uow
            ->expects($this->at(0))
            ->method('clear')
            ->with()
        ;

        $this->consumer
            ->expects($this->once())
            ->method('execute')
            ->with($message)
            ->willReturn(self::NACK)
        ;

        $this->uow
            ->expects($this->at(0))
            ->method('clear')
            ->with()
        ;

        $this->uow
            ->expects($this->never())
            ->method('commit')
        ;

        $result = $consumer->execute($message);

        $this->assertSame(self::NACK, $result);
    }

    public function testException()
    {
        $consumer = new TransactionalSensorConsumer($this->consumer, $this->uow);

        $message = new AMQPMessage();
        $exception = new \RuntimeException();

        $this->consumer
            ->expects($this->once())
            ->method('execute')
            ->with($message)
            ->willThrowException($exception)
        ;

        $this->uow
            ->expects($this->at(0))
            ->method('clear')
            ->with()
        ;

        $this->expectExceptionObject($exception);

        $result = $consumer->execute($message);

        $this->assertSame(self::NACK, $result);
    }
}
