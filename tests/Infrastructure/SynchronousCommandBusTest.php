<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Infrastructure;

use Application\Command;
use Application\CommandHandler;
use Application\Exception\CommandHandlerAlreadyRegistered;
use Application\Exception\CommandNotSupported;
use PHPUnit\Framework\TestCase;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
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

    public function setUp()
    {
        $this->handler1 = $this->getMockBuilder(CommandHandler::class)->setMockClassName('handler1')->getMockForAbstractClass();
        $this->handler2 = $this->getMockBuilder(CommandHandler::class)->setMockClassName('handler2')->getMockForAbstractClass();
        $this->handler3 = $this->getMockBuilder(CommandHandler::class)->setMockClassName('handler3')->getMockForAbstractClass();

        $this->command1 = $this->getMockBuilder(Command::class)->setMockClassName('command1')->getMockForAbstractClass();
    }

    public function testAlreadyExistingHandler()
    {
        $bus = new SynchronousCommandBus();

        $bus->registerHandler($this->handler1);
        $bus->registerHandler($this->handler2);
        $bus->registerHandler($this->handler3);

        $this->expectException(CommandHandlerAlreadyRegistered::class);

        $bus->registerHandler($this->handler1);
    }

    public function testCommandHandling()
    {
        $bus = new SynchronousCommandBus();

        $bus->registerHandler($this->handler1);
        $bus->registerHandler($this->handler2);
        $bus->registerHandler($this->handler3);

        $this->handler1
            ->expects($this->once())
            ->method('supports')
            ->with($this->command1)
            ->willReturn(false)
        ;
        $this->handler1
            ->expects($this->never())
            ->method('handle')
        ;
        $this->handler2
            ->expects($this->once())
            ->method('supports')
            ->with($this->command1)
            ->willReturn(true)
        ;
        $this->handler2
            ->expects($this->once())
            ->method('handle')
            ->with($this->command1)
        ;
        $this->handler3
            ->expects($this->never())
            ->method('supports');
        ;
        $this->handler3
            ->expects($this->never())
            ->method('handle');
        ;

        $bus->dispatch($this->command1);
    }

    public function testNoHandlerForCommand()
    {
        $bus = new SynchronousCommandBus();

        $bus->registerHandler($this->handler1);
        $bus->registerHandler($this->handler2);

        $this->handler1
            ->expects($this->once())
            ->method('supports')
            ->with($this->command1)
            ->willReturn(false)
        ;
        $this->handler1
            ->expects($this->never())
            ->method('handle')
        ;
        $this->handler2
            ->expects($this->once())
            ->method('supports')
            ->with($this->command1)
            ->willReturn(false)
        ;
        $this->handler2
            ->expects($this->never())
            ->method('handle')
        ;

        $this->expectException(CommandNotSupported::class);

        $bus->dispatch($this->command1);
    }
}
