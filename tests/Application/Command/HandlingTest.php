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

namespace Streak\Application\Command;

use PHPUnit\Framework\TestCase;
use Streak\Application\Command;
use Streak\Application\Command\HandlingTest\CommandHandlingStub;
use Streak\Application\Command\HandlingTest\NotSupportedCommand1;
use Streak\Application\Command\HandlingTest\SupportedCommand1;
use Streak\Application\Command\HandlingTest\SupportedCommand2;
use Streak\Application\Command\HandlingTest\SupportedCommand3;
use Streak\Application\Command\HandlingTest\SupportedCommand4;
use Streak\Application\Exception\CommandNotSupported;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Application\Command\Handling
 */
class HandlingTest extends TestCase
{
    public function testSuccess()
    {
        $handler = new CommandHandlingStub();

        $handler->handle(new SupportedCommand1());
        $handler->handle(new SupportedCommand2());
        $handler->handle(new SupportedCommand3());
        $handler->handle(new SupportedCommand4());

        $handled = [
            'Streak\Application\Command\HandlingTest\CommandHandlingStub::handle1',
            'Streak\Application\Command\HandlingTest\CommandHandlingStub::handle2',
            'Streak\Application\Command\HandlingTest\CommandHandlingStub::handle3',
            'Streak\Application\Command\HandlingTest\CommandHandlingStub::handle4',
        ];

        $this->assertSame($handled, $handler->handled());
    }

    /**
     * @dataProvider failingQueries
     */
    public function testFailure(Command $query)
    {
        $this->expectExceptionObject(new CommandNotSupported($query));

        $handler = new CommandHandlingStub();
        $handler->handle($query);
    }

    public function failingQueries() : array
    {
        return [
            [new NotSupportedCommand1()],
        ];
    }
}

namespace Streak\Application\Command\HandlingTest;

use Streak\Application\Command;
use Streak\Application\CommandHandler;

class CommandHandlingStub implements CommandHandler
{
    use Command\Handling;

    private array $handled = [];

    public function handle1(SupportedCommand1 $command)
    {
        $this->handled[] = __METHOD__;
    }

    public function handle2(SupportedCommand2 $command)
    {
        $this->handled[] = __METHOD__;
    }

    public function handle3(SupportedCommand3 $command)
    {
        $this->handled[] = __METHOD__;
    }

    public function handle4(SupportedCommand4 $command)
    {
        $this->handled[] = __METHOD__;
    }

    public function notStartingWithHandle(NotSupportedCommand1 $command)
    {
        $this->handled[] = __METHOD__;
    }

    public function handleHandlingMethodWithMoreThanOneParameter1(SupportedCommand1 $command1, SupportedCommand2 $command2)
    {
        $this->handled[] = __METHOD__;
    }

    public function handleHandlingMethodWithMoreThanOneParameter2(SupportedCommand1 $command1, SupportedCommand2 $command2, NotSupportedCommand1 $command3)
    {
        $this->handled[] = __METHOD__;
    }

    public function handleNotRequiredCommandParameter(?SupportedCommand1 $command1)
    {
        $this->handled[] = __METHOD__;
    }

    public function handleNonCommandParameter(\stdClass $command1)
    {
        $this->handled[] = __METHOD__;
    }

    public function handled() : array
    {
        return $this->handled;
    }

    private function handlePrivateMethodHandlingMethod(NotSupportedCommand1 $command)
    {
        $this->handled[] = __METHOD__;
    }
}

class SupportedCommand1 implements Command
{
}

class SupportedCommand2 implements Command
{
}

class SupportedCommand3 implements Command
{
}

class SupportedCommand4 implements Command
{
}

class NotSupportedCommand1 implements Command
{
}
