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

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Infrastructure\Domain\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Interfaces\RabbitMQ\CommittingSensorConsumer
 */
class CommittingSensorConsumerTest extends TestCase
{
    private const ACK = true;
    private const NACK = false;

    private ConsumerInterface|MockObject $consumer;

    private UnitOfWork|MockObject $uow;

    protected function setUp(): void
    {
        $this->consumer = $this->getMockBuilder(ConsumerInterface::class)->getMockForAbstractClass();
        $this->uow = $this->getMockBuilder(UnitOfWork::class)->getMockForAbstractClass();
    }

    public function testAck(): void
    {
        $consumer = new CommittingSensorConsumer($this->consumer, $this->uow);

        $message = new AMQPMessage();

        $this->uow
            ->expects(self::at(0))
            ->method('clear')
            ->with()
        ;

        $this->consumer
            ->expects(self::once())
            ->method('execute')
            ->with($message)
            ->willReturn(self::ACK)
        ;

        $this->uow
            ->expects(self::at(1))
            ->method('commit')
            ->with()
        ;

        $this->uow
            ->expects(self::at(0))
            ->method('clear')
            ->with()
        ;

        $result = $consumer->execute($message);

        self::assertSame(self::ACK, $result);
    }

    public function testNack(): void
    {
        $consumer = new CommittingSensorConsumer($this->consumer, $this->uow);

        $message = new AMQPMessage();

        $this->uow
            ->expects(self::at(0))
            ->method('clear')
            ->with()
        ;

        $this->consumer
            ->expects(self::once())
            ->method('execute')
            ->with($message)
            ->willReturn(self::NACK)
        ;

        $this->uow
            ->expects(self::at(0))
            ->method('clear')
            ->with()
        ;

        $this->uow
            ->expects(self::never())
            ->method('commit')
        ;

        $result = $consumer->execute($message);

        self::assertSame(self::NACK, $result);
    }

    public function testException(): void
    {
        $consumer = new CommittingSensorConsumer($this->consumer, $this->uow);

        $message = new AMQPMessage();
        $exception = new \RuntimeException();

        $this->consumer
            ->expects(self::once())
            ->method('execute')
            ->with($message)
            ->willThrowException($exception)
        ;

        $this->uow
            ->expects(self::at(0))
            ->method('clear')
            ->with()
        ;

        $this->expectExceptionObject($exception);

        $result = $consumer->execute($message);

        self::assertSame(self::NACK, $result);
    }
}
