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

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Application\CommandBus\LockableCommandBus
 */
class LockableCommandBusTest extends TestCase
{
    private CommandBus $bus;

    private Command $command1;
    private Command $command2;
    private Command $command3;
    private Command $command4;
    private Command $command5;

    protected function setUp(): void
    {
        $this->bus = $this->getMockBuilder(CommandBus::class)->getMockForAbstractClass();

        $this->command1 = $this->getMockBuilder(Command::class)->setMockClassName('command1')->getMockForAbstractClass();
        $this->command2 = $this->getMockBuilder(Command::class)->setMockClassName('command2')->getMockForAbstractClass();
        $this->command3 = $this->getMockBuilder(Command::class)->setMockClassName('command3')->getMockForAbstractClass();
        $this->command4 = $this->getMockBuilder(Command::class)->setMockClassName('command3')->getMockForAbstractClass();
        $this->command5 = $this->getMockBuilder(Command::class)->setMockClassName('command3')->getMockForAbstractClass();
    }

    public function testBus(): void
    {
        $this->bus
            ->expects(self::exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [$this->command1],
                [$this->command4],
                [$this->command5],
            )
        ;

        $bus = new LockableCommandBus($this->bus);
        $bus->dispatch($this->command1);

        $bus->lock();

        $bus->dispatch($this->command2);
        $bus->dispatch($this->command3);

        $bus->unlock();

        $bus->dispatch($this->command4);
        $bus->dispatch($this->command5);
    }
}
