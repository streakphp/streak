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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Application\Command;
use Streak\Application\CommandHandler;
use Streak\Application\Exception\CommandNotSupported;
use Streak\Domain\AggregateRoot;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Application\CommandHandler\AggregateRootHandler
 */
class AggregateRootHandlerTest extends TestCase
{
    /**
     * @var AggregateRoot\Factory|MockObject
     */
    private $factory;

    /**
     * @var AggregateRoot\Repository|MockObject
     */
    private $repository;

    /**
     * @var Command|MockObject
     */
    private $command;

    /**
     * @var AggregateRoot|MockObject
     */
    private $aggregateRoot;

    /**
     * @var AggregateRoot|CommandHandler|MockObject
     */
    private $aggregateRootCommandHandler;

    /**
     * @var Command\AggregateRootCommand|MockObject
     */
    private $aggregateRootCommand;

    /**
     * @var AggregateRoot\Id|MockObject
     */
    private $aggregateRootId;

    protected function setUp()
    {
        $this->factory = $this->getMockBuilder(AggregateRoot\Factory::class)->getMockForAbstractClass();
        $this->repository = $this->getMockBuilder(AggregateRoot\Repository::class)->getMockForAbstractClass();
        $this->command = $this->getMockBuilder(Command::class)->getMockForAbstractClass();
        $this->aggregateRoot = $this->getMockBuilder(AggregateRoot::class)->getMockForAbstractClass();
        $this->aggregateRootId = $this->getMockBuilder(AggregateRoot\Id::class)->getMockForAbstractClass();
        $this->aggregateRootCommand = $this->getMockBuilder(Command\AggregateRootCommand::class)->getMockForAbstractClass();
        $this->aggregateRootCommandHandler = $this->getMockBuilder([AggregateRoot::class, CommandHandler::class])->getMock();
    }

    public function testCommandNotSupported()
    {
        $this->expectExceptionObject(new CommandNotSupported($this->command));

        $handler = new AggregateRootHandler($this->factory, $this->repository);
        $handler->handle($this->command);
    }

    public function testCommandHandlingAggregateNotFound()
    {
        $this->aggregateRootCommand
            ->expects($this->atLeastOnce())
            ->method('aggregateRootId')
            ->willReturn($this->aggregateRootId)
        ;

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with($this->aggregateRootId)
            ->willReturn(null)
        ;

        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($this->aggregateRootId)
            ->willReturn($this->aggregateRootCommandHandler)
        ;

        $this->aggregateRootCommandHandler
            ->expects($this->once())
            ->method('handle')
            ->with($this->aggregateRootCommand)
        ;

        $handler = new AggregateRootHandler($this->factory, $this->repository);
        $handler->handle($this->aggregateRootCommand);
    }

    public function testNonCommandHandlingAggregateNotFound()
    {
        $this->expectExceptionObject(new CommandNotSupported($this->aggregateRootCommand));

        $this->aggregateRootCommand
            ->expects($this->atLeastOnce())
            ->method('aggregateRootId')
            ->willReturn($this->aggregateRootId)
        ;

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with($this->aggregateRootId)
            ->willReturn(null)
        ;

        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($this->aggregateRootId)
            ->willReturn($this->aggregateRoot)
        ;

        $this->aggregateRootCommandHandler
            ->expects($this->never())
            ->method($this->anything())
        ;

        $handler = new AggregateRootHandler($this->factory, $this->repository);
        $handler->handle($this->aggregateRootCommand);
    }

    public function testAggregateNotACommandHandler()
    {
        $this->expectExceptionObject(new CommandNotSupported($this->aggregateRootCommand));

        $this->aggregateRootCommand
            ->expects($this->atLeastOnce())
            ->method('aggregateRootId')
            ->willReturn($this->aggregateRootId)
        ;
        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with($this->aggregateRootId)
            ->willReturn($this->aggregateRoot)
        ;

        $this->aggregateRootCommandHandler
            ->expects($this->never())
            ->method($this->anything())
        ;

        $handler = new AggregateRootHandler($this->factory, $this->repository);
        $handler->handle($this->aggregateRootCommand);
    }

    public function testCommandHandlingAggregateRoot()
    {
        $this->aggregateRootCommand
            ->expects($this->atLeastOnce())
            ->method('aggregateRootId')
            ->willReturn($this->aggregateRootId)
        ;
        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with($this->aggregateRootId)
            ->willReturn($this->aggregateRootCommandHandler)
        ;
        $this->aggregateRootCommandHandler
            ->expects($this->once())
            ->method('handle')
            ->with($this->aggregateRootCommand)
        ;

        $handler = new AggregateRootHandler($this->factory, $this->repository);
        $handler->handle($this->aggregateRootCommand);
    }
}
