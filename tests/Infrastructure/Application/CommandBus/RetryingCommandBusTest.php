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

namespace Streak\Infrastructure\Application\CommandBus;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Application\CommandBus;
use Streak\Domain;
use Streak\Domain\Command;
use Streak\Domain\Exception;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Application\CommandBus\RetryingCommandBus
 */
class RetryingCommandBusTest extends TestCase
{
    private CommandBus|MockObject $bus;

    private Command|MockObject $command1;
    private Command|MockObject $command2;
    private Command|MockObject $command3;

    private Domain\Id|MockObject $id;

    private Exception\ConcurrentWriteDetected $exception1;
    private Exception\ConcurrentWriteDetected $exception2;
    private Exception\ConcurrentWriteDetected $exception3;
    private Exception\ConcurrentWriteDetected $exception4;
    private Exception\ConcurrentWriteDetected $exception5;
    private Exception\ConcurrentWriteDetected $exception6;

    protected function setUp(): void
    {
        $this->bus = $this->getMockBuilder(CommandBus::class)->getMockForAbstractClass();
        $this->command1 = $this->getMockBuilder(Command::class)->setMockClassName('command1')->getMockForAbstractClass();
        $this->command2 = $this->getMockBuilder(Command::class)->setMockClassName('command2')->getMockForAbstractClass();
        $this->command3 = $this->getMockBuilder(Command::class)->setMockClassName('command3')->getMockForAbstractClass();
        $this->id = $this->getMockBuilder(Domain\Id::class)->getMockForAbstractClass();
        $this->exception1 = new Exception\ConcurrentWriteDetected($this->id);
        $this->exception2 = new Exception\ConcurrentWriteDetected($this->id, $this->exception1);
        $this->exception3 = new Exception\ConcurrentWriteDetected($this->id, $this->exception2);
        $this->exception4 = new Exception\ConcurrentWriteDetected($this->id, $this->exception3);
        $this->exception5 = new Exception\ConcurrentWriteDetected($this->id, $this->exception4);
        $this->exception6 = new Exception\ConcurrentWriteDetected($this->id, $this->exception5);
    }

    public function testSuccess(): void
    {
        $bus = new RetryingCommandBus($this->bus, 6);

        self::assertSame(6, $bus->maxAttemptsAllowed());
        self::assertSame(0, $bus->numberOfAttempts());

        $this->bus
            ->expects(self::exactly(7))
            ->method('dispatch')
            ->withConsecutive(
                [$this->command1],
                [$this->command1],
                [$this->command1],
                [$this->command1],
                [$this->command2],
                [$this->command2],
                [$this->command3],
            )
            ->willReturnOnConsecutiveCalls(
                self::throwException($this->exception1),
                self::throwException($this->exception2),
                self::throwException($this->exception3),
                null,
                self::throwException($this->exception4),
                null,
                null,
            )
        ;

        $bus->dispatch($this->command1);

        self::assertSame(6, $bus->maxAttemptsAllowed());
        self::assertSame(4, $bus->numberOfAttempts());

        $bus->dispatch($this->command2);

        self::assertSame(6, $bus->maxAttemptsAllowed());
        self::assertSame(2, $bus->numberOfAttempts());

        $bus->dispatch($this->command3);

        self::assertSame(6, $bus->maxAttemptsAllowed());
        self::assertSame(1, $bus->numberOfAttempts());
    }

    public function testError(): void
    {
        $bus = new RetryingCommandBus($this->bus, 6);

        self::assertSame(6, $bus->maxAttemptsAllowed());
        self::assertSame(0, $bus->numberOfAttempts());

        $this->bus
            ->expects(self::exactly(6))
            ->method('dispatch')
            ->with($this->command1)
            ->willReturnOnConsecutiveCalls(
                self::throwException($this->exception1),
                self::throwException($this->exception2),
                self::throwException($this->exception3),
                self::throwException($this->exception4),
                self::throwException($this->exception5),
                self::throwException($this->exception6),
            )
        ;

        $this->expectExceptionObject($this->exception6);

        $bus->dispatch($this->command1);
    }
}
