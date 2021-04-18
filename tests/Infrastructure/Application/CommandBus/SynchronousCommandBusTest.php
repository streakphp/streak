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
use Streak\Domain\Command;
use Streak\Domain\CommandHandler;
use Streak\Domain\Exception\CommandNotSupported;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Application\CommandBus\SynchronousCommandBus
 */
class SynchronousCommandBusTest extends TestCase
{
    private CommandHandler $handler1;
    private CommandHandler $handler2;
    private CommandHandler $handler3;

    private Command $command1;

    protected function setUp(): void
    {
        $this->handler1 = $this->getMockBuilder(CommandHandler::class)->setMockClassName('handler1')->getMockForAbstractClass();
        $this->handler2 = $this->getMockBuilder(CommandHandler::class)->setMockClassName('handler2')->getMockForAbstractClass();
        $this->handler3 = $this->getMockBuilder(CommandHandler::class)->setMockClassName('handler3')->getMockForAbstractClass();

        $this->command1 = $this->getMockBuilder(Command::class)->setMockClassName('command1')->getMockForAbstractClass();
    }

    public function testCommandHandling(): void
    {
        $bus = new SynchronousCommandBus();

        $bus->register($this->handler1);
        $bus->register($this->handler1); // repeated handler does not really change anything, so we allow them
        $bus->register($this->handler2);
        $bus->register($this->handler2);
        $bus->register($this->handler3);

        $exception = new CommandNotSupported($this->command1);

        $this->handler1
            ->expects(self::exactly(2))
            ->method('handleCommand')
            ->with($this->command1)
            ->willThrowException($exception)
        ;
        $this->handler2
            ->expects(self::once())
            ->method('handleCommand')
            ->with($this->command1)
        ;
        $this->handler3
            ->expects(self::never())
            ->method('handleCommand')
        ;

        $bus->dispatch($this->command1);
    }

    public function testNoHandlers(): void
    {
        $bus = new SynchronousCommandBus();

        $exception = new CommandNotSupported($this->command1);

        $this->expectExceptionObject($exception);

        $bus->dispatch($this->command1);
    }

    public function testNoHandlerForCommand(): void
    {
        $bus = new SynchronousCommandBus();

        $bus->register($this->handler1);
        $bus->register($this->handler2);

        $exception = new CommandNotSupported($this->command1);

        $this->handler1
            ->expects(self::once())
            ->method('handleCommand')
            ->with($this->command1)
            ->willThrowException($exception)
        ;
        $this->handler2
            ->expects(self::once())
            ->method('handleCommand')
            ->with($this->command1)
            ->willThrowException($exception)
        ;

        $this->expectExceptionObject(new CommandNotSupported($this->command1));

        $bus->dispatch($this->command1);
    }
}
