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
use Streak\Application\CommandHandler;
use Streak\Application\Exception\CommandHandlerAlreadyRegistered;
use Streak\Application\Exception\CommandNotSupported;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\CommandBus\SynchronousCommandBus
 */
class SynchronousCommandBusTest extends TestCase
{
    /**
     * @var CommandHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $handler1;

    /**
     * @var CommandHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $handler2;

    /**
     * @var CommandHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $handler3;

    /**
     * @var Command|\PHPUnit_Framework_MockObject_MockObject
     */
    private $command1;

    protected function setUp(): void
    {
        $this->handler1 = $this->getMockBuilder(CommandHandler::class)->setMockClassName('handler1')->getMockForAbstractClass();
        $this->handler2 = $this->getMockBuilder(CommandHandler::class)->setMockClassName('handler2')->getMockForAbstractClass();
        $this->handler3 = $this->getMockBuilder(CommandHandler::class)->setMockClassName('handler3')->getMockForAbstractClass();

        $this->command1 = $this->getMockBuilder(Command::class)->setMockClassName('command1')->getMockForAbstractClass();
    }

    public function testAlreadyRegisteredHandler(): void
    {
        $bus = new SynchronousCommandBus();

        $bus->register($this->handler1);
        $bus->register($this->handler2);
        $bus->register($this->handler3);

        $exception = new CommandHandlerAlreadyRegistered($this->handler1);

        $this->expectExceptionObject($exception);

        $bus->register($this->handler1);
    }

    public function testCommandHandling(): void
    {
        $bus = new SynchronousCommandBus();

        $bus->register($this->handler1);
        $bus->register($this->handler2);
        $bus->register($this->handler3);

        $exception = new CommandNotSupported($this->command1);

        $this->handler1
            ->expects(self::once())
            ->method('handle')
            ->with($this->command1)
            ->willThrowException($exception)
        ;
        $this->handler2
            ->expects(self::once())
            ->method('handle')
            ->with($this->command1)
        ;
        $this->handler3
            ->expects(self::never())
            ->method('handle')
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
            ->method('handle')
            ->with($this->command1)
            ->willThrowException($exception)
        ;
        $this->handler2
            ->expects(self::once())
            ->method('handle')
            ->with($this->command1)
            ->willThrowException($exception)
        ;

        $this->expectExceptionObject($exception);

        $bus->dispatch($this->command1);
    }
}
