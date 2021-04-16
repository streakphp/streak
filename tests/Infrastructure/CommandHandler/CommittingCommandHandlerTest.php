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

namespace Streak\Infrastructure\CommandHandler;

use PHPUnit\Framework\TestCase;
use Streak\Application\Command;
use Streak\Application\CommandHandler;
use Streak\Domain\Event;
use Streak\Infrastructure\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\CommandHandler\CommittingCommandHandler
 */
class CommittingCommandHandlerTest extends TestCase
{
    private Command $command;

    private CommandHandler $handler;

    private UnitOfWork $uow;

    private Event\Sourced\AggregateRoot $aggregateRoot1;

    private Event\Producer $producer1;
    private Event\Producer $producer2;

    protected function setUp(): void
    {
        $this->command = $this->getMockBuilder(Command::class)->getMockForAbstractClass();
        $this->handler = $this->getMockBuilder(CommandHandler::class)->getMockForAbstractClass();
        $this->uow = $this->getMockBuilder(UnitOfWork::class)->getMockForAbstractClass();

        $this->aggregateRoot1 = $this->getMockBuilder(Event\Sourced\AggregateRoot::class)->getMockForAbstractClass();

        $this->producer1 = $this->getMockBuilder(Event\Producer::class)->getMockForAbstractClass();
        $this->producer2 = $this->getMockBuilder(Event\Producer::class)->getMockForAbstractClass();
    }

    public function testHandlingCommand(): void
    {
        $handler = new CommittingCommandHandler($this->handler, $this->uow);

        $this->handler
            ->expects(self::once())
            ->method('handle')
            ->with($this->command)
            ->willReturnCallback(function (): void {
                $this->uow->add($this->aggregateRoot1);
            })
        ;

        $this->uow
            ->expects(self::once())
            ->method('commit')
            ->with()
            ->willReturn($this->committed($this->producer1, $this->producer2))
        ;

        $handler->handle($this->command);
    }

    public function committed(Event\Producer ...$producers): \Generator
    {
        foreach ($producers as $producer) {
            yield $producer;
        }
    }
}
