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

use PHPUnit\Framework\TestCase;
use Streak\Application\CommandBus;
use Streak\Domain\Command;
use Streak\Domain\Event;
use Streak\Infrastructure\Domain\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Application\CommandBus\CommittingCommandBus
 */
class CommittingCommandBusTest extends TestCase
{
    private CommandBus $bus;

    private Command $command1;
    private Command $command2;

    private UnitOfWork $uow;

    private Event\Producer $producer1;
    private Event\Producer $producer2;

    protected function setUp(): void
    {
        $this->bus = $this->getMockBuilder(CommandBus::class)->getMockForAbstractClass();
        $this->command1 = $this->getMockBuilder(Command::class)->setMockClassName('command1')->getMockForAbstractClass();
        $this->command2 = $this->getMockBuilder(Command::class)->setMockClassName('command2')->getMockForAbstractClass();
        $this->uow = $this->getMockBuilder(UnitOfWork::class)->getMockForAbstractClass();

        $this->producer1 = $this->getMockBuilder(Event\Producer::class)->getMockForAbstractClass();
        $this->producer2 = $this->getMockBuilder(Event\Producer::class)->getMockForAbstractClass();
    }

    public function testHandlingCommand(): void
    {
        $bus = new CommittingCommandBus($this->bus, $this->uow);

        $this->bus
            ->expects(self::once())
            ->method('dispatch')
            ->with($this->command1)
        ;

        $generator = (function () {
            yield $this->producer1;
            yield $this->producer2;
        })();

        self::assertTrue($generator->valid()); // generator not yet started

        $this->uow
            ->expects(self::once())
            ->method('commit')
            ->with()
            ->willReturn($generator)
        ;

        self::assertSame(0, $bus->transactions());
        $bus->dispatch($this->command1);
        self::assertSame(0, $bus->transactions());

        self::assertFalse($generator->valid()); // generator was iterated over
    }

    public function testHandlingEmbeddedCommand(): void
    {
        $bus = new CommittingCommandBus($this->bus, $this->uow);

        $this->bus
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$this->command1],
                [$this->command2],
            )
            ->willReturnOnConsecutiveCalls(
                self::returnCallback(function (Command $command) use ($bus) {
                    self::assertSame($this->command1, $command);
                    self::assertSame(1, $bus->transactions());
                    $bus->dispatch($this->command2);
                    self::assertSame(1, $bus->transactions());
                }),
                self::returnCallback(function (Command $command) use ($bus) {
                    self::assertSame($this->command2, $command);
                    self::assertSame(2, $bus->transactions());
                }),
            )
        ;

        $generator = (function () {
            yield $this->producer1;
            yield $this->producer2;
        })();

        self::assertTrue($generator->valid()); // generator not yet started

        $this->uow
            ->expects(self::once())
            ->method('commit')
            ->with()
            ->willReturn($generator)
        ;

        self::assertSame(0, $bus->transactions());
        $bus->dispatch($this->command1);
        self::assertSame(0, $bus->transactions());

        self::assertFalse($generator->valid()); // generator was iterated over
    }

    public function testExceptionThrown(): void
    {
        $bus = new CommittingCommandBus($this->bus, $this->uow);

        $this->bus
            ->expects(self::once())
            ->method('dispatch')
            ->with($this->command1)
            ->willThrowException($exception = new \RuntimeException())
        ;

        $this->uow
            ->expects(self::never())
            ->method('commit')
        ;

        try {
            self::assertSame(0, $bus->transactions());
            $bus->dispatch($this->command1);
            self::fail();
        } catch (\Throwable $thrown) {
            self::assertSame(0, $bus->transactions());
            self::assertSame($exception, $thrown);
        }
    }

    public function testExceptionThrownWithinEmbeddedCommand(): void
    {
        $bus = new CommittingCommandBus($this->bus, $this->uow);
        $exception = new \RuntimeException();

        $this->bus
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$this->command1],
                [$this->command2],
            )
            ->willReturnOnConsecutiveCalls(
                self::returnCallback(function (Command $command) use ($bus) {
                    self::assertSame($this->command1, $command);
                    self::assertSame(1, $bus->transactions());
                    $bus->dispatch($this->command2);
                }),
                self::returnCallback(function (Command $command) use ($bus, $exception) {
                    self::assertSame($this->command2, $command);
                    self::assertSame(2, $bus->transactions());

                    throw $exception;
                }),
            )
        ;

        $this->uow
            ->expects(self::never())
            ->method('commit')
        ;

        try {
            self::assertSame(0, $bus->transactions());
            $bus->dispatch($this->command1);
            self::fail();
        } catch (\Throwable $thrown) {
            self::assertSame(0, $bus->transactions());
            self::assertSame($exception, $thrown);
        }
    }
}
