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

namespace Streak\Application\CommandHandler;

use PHPUnit\Framework\TestCase;
use Streak\Application\Command;
use Streak\Application\CommandHandler\AggregateRootHandlerTest\CommandHandlingAggregateRoot;
use Streak\Application\Exception\CommandNotSupported;
use Streak\Domain\AggregateRoot;
use Streak\Domain\Exception\AggregateNotFound;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Application\CommandHandler\AggregateRootHandler
 */
class AggregateRootHandlerTest extends TestCase
{
    private AggregateRoot\Repository $repository;
    private Command $command;
    private AggregateRoot $aggregateRoot;
    private AggregateRoot\Id $aggregateRootId;
    private Command\AggregateRootCommand $aggregateRootCommand;
    private CommandHandlingAggregateRoot $aggregateRootCommandHandler;

    protected function setUp(): void
    {
        $this->repository = $this->getMockBuilder(AggregateRoot\Repository::class)->getMockForAbstractClass();
        $this->command = $this->getMockBuilder(Command::class)->getMockForAbstractClass();
        $this->aggregateRoot = $this->getMockBuilder(AggregateRoot::class)->getMockForAbstractClass();
        $this->aggregateRootId = $this->getMockBuilder(AggregateRoot\Id::class)->getMockForAbstractClass();
        $this->aggregateRootCommand = $this->getMockBuilder(Command\AggregateRootCommand::class)->getMockForAbstractClass();
        $this->aggregateRootCommandHandler = $this->getMockBuilder(CommandHandlingAggregateRoot::class)->getMock();
    }

    public function testCommandNotSupported(): void
    {
        $this->expectExceptionObject(new CommandNotSupported($this->command));

        $handler = new AggregateRootHandler($this->repository);
        $handler->handle($this->command);
    }

    public function testAggregateNotFound(): void
    {
        $this->expectExceptionObject(new AggregateNotFound($this->aggregateRootId));

        $this->aggregateRootCommand
            ->expects(self::atLeastOnce())
            ->method('aggregateRootId')
            ->willReturn($this->aggregateRootId)
        ;

        $this->repository
            ->expects(self::once())
            ->method('find')
            ->with($this->aggregateRootId)
            ->willReturn(null)
        ;

        $this->aggregateRootCommandHandler
            ->expects(self::never())
            ->method(self::anything())
        ;

        $handler = new AggregateRootHandler($this->repository);
        $handler->handle($this->aggregateRootCommand);
    }

    public function testAggregateNotACommandHandler(): void
    {
        $this->expectExceptionObject(new CommandNotSupported($this->aggregateRootCommand));

        $this->aggregateRootCommand
            ->expects(self::atLeastOnce())
            ->method('aggregateRootId')
            ->willReturn($this->aggregateRootId)
        ;
        $this->repository
            ->expects(self::once())
            ->method('find')
            ->with($this->aggregateRootId)
            ->willReturn($this->aggregateRoot)
        ;

        $this->aggregateRootCommandHandler
            ->expects(self::never())
            ->method(self::anything())
        ;

        $handler = new AggregateRootHandler($this->repository);
        $handler->handle($this->aggregateRootCommand);
    }

    public function testCommandHandlingAggregateRoot(): void
    {
        $this->aggregateRootCommand
            ->expects(self::atLeastOnce())
            ->method('aggregateRootId')
            ->willReturn($this->aggregateRootId)
        ;
        $this->repository
            ->expects(self::once())
            ->method('find')
            ->with($this->aggregateRootId)
            ->willReturn($this->aggregateRootCommandHandler)
        ;
        $this->aggregateRootCommandHandler
            ->expects(self::once())
            ->method('handle')
            ->with($this->aggregateRootCommand)
        ;

        $handler = new AggregateRootHandler($this->repository);
        $handler->handle($this->aggregateRootCommand);
    }
}

namespace Streak\Application\CommandHandler\AggregateRootHandlerTest;

use Streak\Application\CommandHandler;
use Streak\Domain\AggregateRoot;

abstract class CommandHandlingAggregateRoot implements AggregateRoot, CommandHandler
{
}
