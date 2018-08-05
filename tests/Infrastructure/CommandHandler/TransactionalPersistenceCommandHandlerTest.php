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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Application;
use Streak\Domain;
use Streak\Domain\Event;
use Streak\Infrastructure;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\CommandHandler\TransactionalPersistenceCommandHandler
 */
class TransactionalPersistenceCommandHandlerTest extends TestCase
{
    /**
     * @var Application\Command|MockObject
     */
    private $command;

    /**
     * @var Application\CommandHandler|MockObject
     */
    private $handler;

    /**
     * @var Domain\EventStore|MockObject
     */
    private $store;

    /**
     * @var Infrastructure\UnitOfWork|MockObject
     */
    private $uow;

    /**
     * @var Event\Sourced\AggregateRoot|MockObject
     */
    private $aggregateRoot1;

    /**
     * @var Event\Sourced\AggregateRoot|MockObject
     */
    private $aggregateRoot2;

    /**
     * @var Domain\AggregateRoot\Id|MockObject
     */
    private $aggregateRootId1;

    /**
     * @var Domain\AggregateRoot\Id|MockObject
     */
    private $aggregateRootId2;

    /**
     * @var Event\Producer|MockObject
     */
    private $producer1;

    /**
     * @var Event\Producer|MockObject
     */
    private $producer2;

    public function setUp()
    {
        $this->command = $this->getMockBuilder(Application\Command::class)->getMockForAbstractClass();
        $this->handler = $this->getMockBuilder(Application\CommandHandler::class)->getMockForAbstractClass();
        $this->store = $this->getMockBuilder(Domain\EventStore::class)->getMockForAbstractClass();
        $this->uow = $this->getMockBuilder(Infrastructure\UnitOfWork::class)->getMockForAbstractClass();

        $this->aggregateRoot1 = $this->getMockBuilder(Event\Sourced\AggregateRoot::class)->getMockForAbstractClass();
        $this->aggregateRoot2 = $this->getMockBuilder(Event\Sourced\AggregateRoot::class)->getMockForAbstractClass();

        $this->aggregateRootId1 = $this->getMockBuilder(Domain\AggregateRoot\Id::class)->getMockForAbstractClass();
        $this->aggregateRootId2 = $this->getMockBuilder(Domain\AggregateRoot\Id::class)->getMockForAbstractClass();

        $this->producer1 = $this->getMockBuilder(Event\Producer::class)->getMockForAbstractClass();
        $this->producer2 = $this->getMockBuilder(Event\Producer::class)->getMockForAbstractClass();
    }

    public function testHandlingCommand()
    {
        $handler = new TransactionalPersistenceCommandHandler($this->handler, $this->uow);

        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->with($this->command)
            ->willReturnCallback(function () : void {
                $this->uow->add($this->aggregateRoot1);
            })
        ;

        $this->uow
            ->expects($this->once())
            ->method('commit')
            ->with()
            ->willReturn($this->committed($this->producer1, $this->producer2))
        ;

        $handler->handle($this->command);
    }

    public function committed(Event\Producer ...$producers) : \Generator
    {
        foreach ($producers as $producer) {
            yield $producer;
        }
    }
}
