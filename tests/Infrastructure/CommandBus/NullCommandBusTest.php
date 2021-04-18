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

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\CommandBus\NullCommandBus
 */
class NullCommandBusTest extends TestCase
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

    /**
     * @var Command|\PHPUnit_Framework_MockObject_MockObject
     */
    private $command2;

    /**
     * @var Command|\PHPUnit_Framework_MockObject_MockObject
     */
    private $command3;

    protected function setUp(): void
    {
        $this->handler1 = $this->getMockBuilder(CommandHandler::class)->setMockClassName('handler1')->getMockForAbstractClass();
        $this->handler2 = $this->getMockBuilder(CommandHandler::class)->setMockClassName('handler2')->getMockForAbstractClass();
        $this->handler3 = $this->getMockBuilder(CommandHandler::class)->setMockClassName('handler3')->getMockForAbstractClass();

        $this->command1 = $this->getMockBuilder(Command::class)->setMockClassName('command1')->getMockForAbstractClass();
        $this->command2 = $this->getMockBuilder(Command::class)->setMockClassName('command2')->getMockForAbstractClass();
        $this->command3 = $this->getMockBuilder(Command::class)->setMockClassName('command3')->getMockForAbstractClass();
    }

    public function testBus(): void
    {
        $this->handler1
            ->expects(self::never())
            ->method('handle')
        ;

        $this->handler2
            ->expects(self::never())
            ->method('handle')
        ;

        $this->handler3
            ->expects(self::never())
            ->method('handle')
        ;

        $bus = new NullCommandBus();
        $bus->dispatch($this->command1);
        $bus->register($this->handler1);
        $bus->dispatch($this->command2);
        $bus->register($this->handler2);
        $bus->register($this->handler3);
        $bus->dispatch($this->command3);
    }
}
