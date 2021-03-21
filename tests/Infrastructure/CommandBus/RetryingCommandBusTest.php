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

use PHPUnit\Framework\MockObject\MockObject;
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
    /**
     * @var CommandBus|MockObject
     */
    private $bus;

    /**
     * @var Command|MockObject
     */
    private $command;

    /**
     * @var Domain\Id|MockObject
     */
    private $id;

    /**
     * @var CommandHandler|MockObject
     */
    private $handler;

    private ?Exception\ConcurrentWriteDetected $exception1 = null;

    private ?Exception\ConcurrentWriteDetected $exception2 = null;

    private ?Exception\ConcurrentWriteDetected $exception3 = null;

    private ?Exception\ConcurrentWriteDetected $exception4 = null;

    private ?Exception\ConcurrentWriteDetected $exception5 = null;

    private ?Exception\ConcurrentWriteDetected $exception6 = null;

    protected function setUp() : void
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

    public function testRegister()
    {
        $bus = new RetryingCommandBus($this->bus, 6);

        $this->bus
            ->expects($this->once())
            ->method('register')
            ->with($this->handler)
        ;

        $bus->register($this->handler);
    }

    public function testSuccess()
    {
        $bus = new RetryingCommandBus($this->bus, 6);

        $this->assertSame(6, $bus->maxAttemptsAllowed());
        $this->assertSame(0, $bus->numberOfAttempts());

        $this->bus
            ->expects($this->at(0))
            ->method('dispatch')
            ->with($this->command)
            ->willThrowException($this->exception1)
        ;

        $this->bus
            ->expects($this->at(1))
            ->method('dispatch')
            ->with($this->command)
            ->willThrowException($this->exception2)
        ;

        $this->bus
            ->expects($this->at(2))
            ->method('dispatch')
            ->with($this->command)
            ->willThrowException($this->exception3)
        ;

        $this->bus
            ->expects($this->at(3))
            ->method('dispatch')
            ->with($this->command)
        ;

        $this->bus
            ->expects($this->at(4))
            ->method('dispatch')
            ->with($this->command)
            ->willThrowException($this->exception4)
        ;

        $this->bus
            ->expects($this->at(5))
            ->method('dispatch')
            ->with($this->command)
        ;

        $this->bus
            ->expects($this->at(6))
            ->method('dispatch')
            ->with($this->command)
        ;

        $bus->dispatch($this->command);

        $this->assertSame(6, $bus->maxAttemptsAllowed());
        $this->assertSame(4, $bus->numberOfAttempts());

        $bus->dispatch($this->command);

        $this->assertSame(6, $bus->maxAttemptsAllowed());
        $this->assertSame(2, $bus->numberOfAttempts());

        $bus->dispatch($this->command);

        $this->assertSame(6, $bus->maxAttemptsAllowed());
        $this->assertSame(1, $bus->numberOfAttempts());
    }

    public function testError()
    {
        $bus = new RetryingCommandBus($this->bus, 6);

        $this->assertSame(6, $bus->maxAttemptsAllowed());
        $this->assertSame(0, $bus->numberOfAttempts());

        $this->bus
            ->expects($this->at(0))
            ->method('dispatch')
            ->with($this->command)
            ->willThrowException($this->exception1)
        ;

        $this->bus
            ->expects($this->at(1))
            ->method('dispatch')
            ->with($this->command)
            ->willThrowException($this->exception2)
        ;

        $this->bus
            ->expects($this->at(2))
            ->method('dispatch')
            ->with($this->command)
            ->willThrowException($this->exception3)
        ;

        $this->bus
            ->expects($this->at(3))
            ->method('dispatch')
            ->with($this->command)
            ->willThrowException($this->exception4)
        ;

        $this->bus
            ->expects($this->at(4))
            ->method('dispatch')
            ->with($this->command)
            ->willThrowException($this->exception5)
        ;

        $this->bus
            ->expects($this->at(5))
            ->method('dispatch')
            ->with($this->command)
            ->willThrowException($this->exception6)
        ;

        $this->expectExceptionObject($this->exception6);

        $bus->dispatch($this->command);
    }
}
