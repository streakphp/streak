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

namespace Streak\Domain\Command;

use PHPUnit\Framework\TestCase;
use Streak\Domain\Command;
use Streak\Domain\Command\HandlingTest\CommandHandlingStub;
use Streak\Domain\Command\HandlingTest\NotSupportedCommand1;
use Streak\Domain\Command\HandlingTest\SupportedCommand1;
use Streak\Domain\Command\HandlingTest\SupportedCommand2;
use Streak\Domain\Command\HandlingTest\SupportedCommand3;
use Streak\Domain\Command\HandlingTest\SupportedCommand4;
use Streak\Domain\Exception\CommandNotSupported;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Command\Handling
 */
class HandlingTest extends TestCase
{
    public function testSuccess(): void
    {
        $handler = new CommandHandlingStub();

        $handler->handleCommand(new SupportedCommand1());
        $handler->handleCommand(new SupportedCommand2());
        $handler->handleCommand(new SupportedCommand3());
        $handler->handleCommand(new SupportedCommand4());

        $handled = [
            'Streak\Domain\Command\HandlingTest\CommandHandlingStub::handle1',
            'Streak\Domain\Command\HandlingTest\CommandHandlingStub::handle2',
            'Streak\Domain\Command\HandlingTest\CommandHandlingStub::handle3',
            'Streak\Domain\Command\HandlingTest\CommandHandlingStub::handle4',
        ];

        self::assertSame($handled, $handler->handled());
    }

    /**
     * @dataProvider failingQueries
     */
    public function testFailure(Command $query): void
    {
        $this->expectExceptionObject(new CommandNotSupported($query));

        $handler = new CommandHandlingStub();
        $handler->handleCommand($query);
    }

    public function failingQueries(): array
    {
        return [
            [new NotSupportedCommand1()],
        ];
    }
}

namespace Streak\Domain\Command\HandlingTest;

use Streak\Domain\Command;
use Streak\Domain\CommandHandler;

class CommandHandlingStub implements CommandHandler
{
    use Command\Handling;

    private array $handled = [];

    public function handle1(SupportedCommand1 $command): void
    {
        $this->handled[] = __METHOD__;
    }

    public function handle2(SupportedCommand2 $command): void
    {
        $this->handled[] = __METHOD__;
    }

    public function handle3(SupportedCommand3 $command): void
    {
        $this->handled[] = __METHOD__;
    }

    public function handle4(SupportedCommand4 $command): void
    {
        $this->handled[] = __METHOD__;
    }

    public function notStartingWithHandle(NotSupportedCommand1 $command): void
    {
        $this->handled[] = __METHOD__;
    }

    public function handleHandlingMethodWithMoreThanOneParameter1(SupportedCommand1 $command1, SupportedCommand2 $command2): void
    {
        $this->handled[] = __METHOD__;
    }

    public function handleHandlingMethodWithMoreThanOneParameter2(SupportedCommand1 $command1, SupportedCommand2 $command2, NotSupportedCommand1 $command3): void
    {
        $this->handled[] = __METHOD__;
    }

    public function handleNotRequiredCommandParameter(?SupportedCommand1 $command1): void
    {
        $this->handled[] = __METHOD__;
    }

    public function handleNonCommandParameter(\stdClass $command1): void
    {
        $this->handled[] = __METHOD__;
    }

    public function handleUnionParameter(SupportedCommand1|SupportedCommand2 $command1): void
    {
        $this->handled[] = __METHOD__;
    }

    public function handleNonVoidReturnType(SupportedCommand1 $command1): \ArrayAccess
    {
        $this->handled[] = __METHOD__;

        return new \ArrayObject();
    }

    public function handleUnionReturnType(SupportedCommand1 $command1): \ArrayAccess|\stdClass
    {
        $this->handled[] = __METHOD__;

        return new \ArrayObject();
    }

    public function handled(): array
    {
        return $this->handled;
    }

    private function handlePrivateMethodHandlingMethod(NotSupportedCommand1 $command): void
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
