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
use Streak\Application\CommandBus;
use Streak\Application\CommandHandler;
use Streak\Infrastructure\CommandBus\LockableCommandBusTest\Command1;
use Streak\Infrastructure\CommandBus\LockableCommandBusTest\Command2;
use Streak\Infrastructure\CommandBus\LockableCommandBusTest\Command3;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\CommandBus\LockableCommandBus
 */
class LockableCommandBusTest extends TestCase
{
    /**
     * @var CommandBus|MockObject
     */
    private $bus;

    /**
     * @var CommandHandler|MockObject
     */
    private $handler1;

    /**
     * @var CommandHandler|MockObject
     */
    private $handler2;

    /**
     * @var CommandHandler|MockObject
     */
    private $handler3;

    protected function setUp()
    {
        $this->bus = $this->getMockBuilder(CommandBus::class)->getMockForAbstractClass();
        $this->handler1 = $this->getMockBuilder(CommandHandler::class)->getMockForAbstractClass();
        $this->handler2 = $this->getMockBuilder(CommandHandler::class)->getMockForAbstractClass();
        $this->handler3 = $this->getMockBuilder(CommandHandler::class)->getMockForAbstractClass();
    }

    public function testBus()
    {
        $this->bus
            ->expects($this->exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [new Command1()],
                [new Command3()],
                [new Command2()]
            )
        ;
        $this->bus
            ->expects($this->exactly(3))
            ->method('register')
            ->withConsecutive(
                [$this->handler1],
                [$this->handler2],
                [$this->handler3]
            )
        ;

        $bus = new LockableCommandBus($this->bus);
        $bus->register($this->handler1);
        $bus->dispatch(new Command1());

        $bus->lock();

        $bus->register($this->handler2);
        $bus->dispatch(new Command2());

        $bus->unlock();

        $bus->register($this->handler3);
        $bus->dispatch(new Command3());
        $bus->dispatch(new Command2());
    }
}

namespace Streak\Infrastructure\CommandBus\LockableCommandBusTest;

use Streak\Application\Command;

class Command1 implements Command
{
}

class Command2 implements Command
{
}

class Command3 implements Command
{
}
