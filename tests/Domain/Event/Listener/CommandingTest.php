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

namespace Streak\Domain\Event\Listener;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Application\CommandBus;
use Streak\Domain\Event\Listener\CommandingTest\Command1;
use Streak\Domain\Event\Listener\CommandingTest\Command2;
use Streak\Domain\Event\Listener\CommandingTest\Command3;
use Streak\Domain\Event\Listener\CommandingTest\CommandingStub;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Listener\Commanding
 */
class CommandingTest extends TestCase
{
    /**
     * @var CommandBus|MockObject
     */
    private $bus;

    protected function setUp()
    {
        $this->bus = $this->getMockBuilder(CommandBus::class)->getMockForAbstractClass();
    }

    public function testReplayingEmptyStream()
    {
        $this->bus
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [new Command1()],
                [new Command3()]
            )
        ;
        $commander = new CommandingStub($this->bus);
        $commander->dispatch(new Command1());
        $commander->muteCommands();
        $commander->dispatch(new Command1());
        $commander->dispatch(new Command2());
        $commander->dispatch(new Command3());
        $commander->unmuteCommands();
        $commander->dispatch(new Command3());
    }
}

namespace Streak\Domain\Event\Listener\CommandingTest;

use Streak\Application\Command;
use Streak\Domain\Event;

class CommandingStub
{
    use Event\Listener\Commanding {
        muteCommands as public;
        unmuteCommands as public;
    }

    private $listened = [];

    public function dispatch(Command $command) : void
    {
        $this->bus->dispatch($command);
    }
}

class Command1 implements Command
{
}
class Command2 implements Command
{
}
class Command3 implements Command
{
}
