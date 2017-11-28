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
     * @var Domain\AggregateRootId|\PHPUnit_Framework_MockObject_MockObject
     */
    private $id1;

    /**
     * @var Domain\AggregateRootId|\PHPUnit_Framework_MockObject_MockObject
     */
    private $id2;

    public function setUp()
    {
        $this->command = $this->getMockBuilder(Application\Command::class)->getMockForAbstractClass();
        $this->handler = $this->getMockBuilder(Application\CommandHandler::class)->getMockForAbstractClass();
        $this->store = $this->getMockBuilder(Domain\EventStore::class)->getMockForAbstractClass();
        $this->uow = new Infrastructure\UnitOfWork($this->store);

        $this->id1 = $this->getMockBuilder(Domain\AggregateRootId::class)->getMockForAbstractClass();
        $this->id2 = $this->getMockBuilder(Domain\AggregateRootId::class)->getMockForAbstractClass();
    }

    public function testHandlingCommand()
    {
        $handler = new TransactionalPersistenceCommandHandler($this->handler, $this->uow);

        $aggregate = new EventSourcedAggregateRootStub($this->id1);

        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->with($this->command)
            ->willReturnCallback(
                function (Application\Command $command) use ($aggregate) : void {
                    $this->uow->add($aggregate);
                }
            )
        ;

        $handler->handle($this->command);
    }

    public function testTransactionCompromise()
    {
        $handler = new TransactionalPersistenceCommandHandler($this->handler, $this->uow);

        $aggregate1 = new EventSourcedAggregateRootStub($this->id1);
        $aggregate2 = new EventSourcedAggregateRootStub($this->id2);

        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->with($this->command)
            ->willReturnCallback(
                function () use ($aggregate1, $aggregate2) : void {
                    $this->uow->add($aggregate1);
                    $this->uow->add($aggregate2);
                }
            )
        ;

        $exception = new Application\Exception\CommandTransactionCompromised($this->command);
        $this->expectExceptionObject($exception);

        $handler->handle($this->command);
    }
}

class EventSourcedAggregateRootStub extends Domain\EventSourced\AggregateRoot
{
}
