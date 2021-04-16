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

namespace Streak\Infrastructure\CommandBus;

use PHPUnit\Framework\TestCase;
use Streak\Application\Command;
use Streak\Application\CommandBus;
use Streak\Application\CommandHandler;
use Streak\Domain;
use Streak\Domain\Exception;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\CommandBus\RetryingCommandBus
 */
class RetryingCommandBusTest extends TestCase
{
    private CommandBus $bus;

    private Command $command;

    private Domain\Id $id;

    private CommandHandler $handler;

    private Exception\ConcurrentWriteDetected $exception1;
    private Exception\ConcurrentWriteDetected $exception2;
    private Exception\ConcurrentWriteDetected $exception3;
    private Exception\ConcurrentWriteDetected $exception4;
    private Exception\ConcurrentWriteDetected $exception5;
    private Exception\ConcurrentWriteDetected $exception6;

    protected function setUp(): void
    {
        $this->bus = $this->getMockBuilder(CommandBus::class)->getMockForAbstractClass();
        $this->command = $this->getMockBuilder(Command::class)->getMockForAbstractClass();
        $this->id = $this->getMockBuilder(Domain\Id::class)->getMockForAbstractClass();
        $this->exception1 = new Exception\ConcurrentWriteDetected($this->id);
        $this->handler = $this->getMockBuilder(CommandHandler::class)->getMockForAbstractClass();
        $this->exception2 = new Exception\ConcurrentWriteDetected($this->id, $this->exception1);
        $this->exception3 = new Exception\ConcurrentWriteDetected($this->id, $this->exception2);
        $this->exception4 = new Exception\ConcurrentWriteDetected($this->id, $this->exception3);
        $this->exception5 = new Exception\ConcurrentWriteDetected($this->id, $this->exception4);
        $this->exception6 = new Exception\ConcurrentWriteDetected($this->id, $this->exception5);
    }

    public function testRegister(): void
    {
        $bus = new RetryingCommandBus($this->bus, 6);

        $this->bus
            ->expects(self::once())
            ->method('register')
            ->with($this->handler)
        ;

        $bus->register($this->handler);
    }

    public function testSuccess(): void
    {
        $bus = new RetryingCommandBus($this->bus, 6);

        self::assertSame(6, $bus->maxAttemptsAllowed());
        self::assertSame(0, $bus->numberOfAttempts());

        $this->bus
            ->expects(self::at(0))
            ->method('dispatch')
            ->with($this->command)
            ->willThrowException($this->exception1)
        ;

        $this->bus
            ->expects(self::at(1))
            ->method('dispatch')
            ->with($this->command)
            ->willThrowException($this->exception2)
        ;

        $this->bus
            ->expects(self::at(2))
            ->method('dispatch')
            ->with($this->command)
            ->willThrowException($this->exception3)
        ;

        $this->bus
            ->expects(self::at(3))
            ->method('dispatch')
            ->with($this->command)
        ;

        $this->bus
            ->expects(self::at(4))
            ->method('dispatch')
            ->with($this->command)
            ->willThrowException($this->exception4)
        ;

        $this->bus
            ->expects(self::at(5))
            ->method('dispatch')
            ->with($this->command)
        ;

        $this->bus
            ->expects(self::at(6))
            ->method('dispatch')
            ->with($this->command)
        ;

        $bus->dispatch($this->command);

        self::assertSame(6, $bus->maxAttemptsAllowed());
        self::assertSame(4, $bus->numberOfAttempts());

        $bus->dispatch($this->command);

        self::assertSame(6, $bus->maxAttemptsAllowed());
        self::assertSame(2, $bus->numberOfAttempts());

        $bus->dispatch($this->command);

        self::assertSame(6, $bus->maxAttemptsAllowed());
        self::assertSame(1, $bus->numberOfAttempts());
    }

    public function testError(): void
    {
        $bus = new RetryingCommandBus($this->bus, 6);

        self::assertSame(6, $bus->maxAttemptsAllowed());
        self::assertSame(0, $bus->numberOfAttempts());

        $this->bus
            ->expects(self::at(0))
            ->method('dispatch')
            ->with($this->command)
            ->willThrowException($this->exception1)
        ;

        $this->bus
            ->expects(self::at(1))
            ->method('dispatch')
            ->with($this->command)
            ->willThrowException($this->exception2)
        ;

        $this->bus
            ->expects(self::at(2))
            ->method('dispatch')
            ->with($this->command)
            ->willThrowException($this->exception3)
        ;

        $this->bus
            ->expects(self::at(3))
            ->method('dispatch')
            ->with($this->command)
            ->willThrowException($this->exception4)
        ;

        $this->bus
            ->expects(self::at(4))
            ->method('dispatch')
            ->with($this->command)
            ->willThrowException($this->exception5)
        ;

        $this->bus
            ->expects(self::at(5))
            ->method('dispatch')
            ->with($this->command)
            ->willThrowException($this->exception6)
        ;

        $this->expectExceptionObject($this->exception6);

        $bus->dispatch($this->command);
    }
}
