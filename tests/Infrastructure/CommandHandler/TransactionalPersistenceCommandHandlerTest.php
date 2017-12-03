<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Infrastructure\CommandHandler;

use PHPUnit\Framework\TestCase;
use Streak\Application;
use Streak\Domain;
use Streak\Domain\Entity;
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
     * @var Application\Command|\PHPUnit_Framework_MockObject_MockObject
     */
    private $command;

    /**
     * @var Application\CommandHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $handler;

    /**
     * @var Domain\EventStore|\PHPUnit_Framework_MockObject_MockObject
     */
    private $store;

    /**
     * @var Infrastructure\UnitOfWork
     */
    private $uow;

    /**
     * @var Event\Sourced\AggregateRoot|\PHPUnit_Framework_MockObject_MockObject
     */
    private $aggregateRoot1;

    /**
     * @var Event\Sourced\AggregateRoot|\PHPUnit_Framework_MockObject_MockObject
     */
    private $aggregateRoot2;

    /**
     * @var Domain\AggregateRoot\Id|\PHPUnit_Framework_MockObject_MockObject
     */
    private $aggregateRootId1;

    /**
     * @var Domain\AggregateRoot\Id|\PHPUnit_Framework_MockObject_MockObject
     */
    private $aggregateRootId2;

    public function setUp()
    {
        $this->command = $this->getMockBuilder(Application\Command::class)->getMockForAbstractClass();
        $this->handler = $this->getMockBuilder(Application\CommandHandler::class)->getMockForAbstractClass();
        $this->store = $this->getMockBuilder(Domain\EventStore::class)->getMockForAbstractClass();
        $this->uow = new Infrastructure\UnitOfWork($this->store);

        $this->aggregateRoot1 = $this->getMockBuilder(Event\Sourced\AggregateRoot::class)->getMockForAbstractClass();
        $this->aggregateRoot2 = $this->getMockBuilder(Event\Sourced\AggregateRoot::class)->getMockForAbstractClass();

        $this->aggregateRootId1 = $this->getMockBuilder(Domain\AggregateRoot\Id::class)->getMockForAbstractClass();
        $this->aggregateRootId2 = $this->getMockBuilder(Domain\AggregateRoot\Id::class)->getMockForAbstractClass();
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

        $handler->handle($this->command);
    }

    public function testTransactionCompromise()
    {
        $handler = new TransactionalPersistenceCommandHandler($this->handler, $this->uow);

        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->with($this->command)
            ->willReturnCallback(function () : void {
                $this->uow->add($this->aggregateRoot1);
                $this->uow->add($this->aggregateRoot2);
            })
        ;

        $exception = new Application\Exception\CommandTransactionCompromised($this->command);
        $this->expectExceptionObject($exception);

        $handler->handle($this->command);
    }
}
