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
use Streak\Domain\AggregateRoot\Repository;
use Streak\Domain\Exception\AggregateNotFound;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Application\CommandHandler\AggregateRootHandler
 */
class AggregateRootHandlerTest extends TestCase
{
    /**
     * @var Repository|MockObject
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
        $this->repository = $this->getMockBuilder(Repository::class)->getMockForAbstractClass();
        $this->command = $this->getMockBuilder(Command::class)->getMockForAbstractClass();
        $this->aggregateRoot = $this->getMockBuilder(AggregateRoot::class)->getMockForAbstractClass();
        $this->aggregateRootId = $this->getMockBuilder(AggregateRoot\Id::class)->getMockForAbstractClass();
        $this->aggregateRootCommand = $this->getMockBuilder(Command\AggregateRootCommand::class)->getMockForAbstractClass();
        $this->aggregateRootCommandHandler = $this->getMockBuilder([AggregateRoot::class, CommandHandler::class])->getMock();
    }

    public function testCommandNotSupported()
    {
        $this->expectExceptionObject(new CommandNotSupported($this->command));

        $handler = new AggregateRootHandler($this->repository);
        $handler->handle($this->command);
    }

    public function testAggregateNotFound()
    {
        $this->expectExceptionObject(new AggregateNotFound($this->aggregateRootId));

        $this->aggregateRootCommand
            ->expects($this->once())
            ->method('aggregateRootId')
            ->willReturn($this->aggregateRootId)
        ;

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with($this->aggregateRootId)
            ->willReturn(null)
        ;

        $handler = new AggregateRootHandler($this->repository);
        $handler->handle($this->aggregateRootCommand);
    }

    public function testAggregateNotACommandHandler()
    {
        $this->expectExceptionObject(new CommandNotSupported($this->aggregateRootCommand));

        $this->aggregateRootCommand
            ->expects($this->once())
            ->method('aggregateRootId')
            ->willReturn($this->aggregateRootId)
        ;
        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with($this->aggregateRootId)
            ->willReturn($this->aggregateRoot)
        ;

        $handler = new AggregateRootHandler($this->repository);
        $handler->handle($this->aggregateRootCommand);
    }

    public function testCommandHandlingAggregateRoot()
    {
        $this->aggregateRootCommand
            ->expects($this->once())
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

        $handler = new AggregateRootHandler($this->repository);
        $handler->handle($this->aggregateRootCommand);
    }
}
