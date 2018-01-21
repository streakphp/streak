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

namespace Streak\Infrastructure\Event\Subscription;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionListenedToEvent;
use Streak\Domain\EventStore;
use Streak\Domain\Exception;
use Streak\Infrastructure\Event\InMemoryStream;
use Streak\Infrastructure\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Event\Subscription\EventSourcedRepository
 */
class EventSourcedRepositoryTest extends TestCase
{
    /**
     * @var Event\Listener\Factory|MockObject
     */
    private $listeners;

    /**
     * @var Event\Subscription\Factory|MockObject
     */
    private $subscriptions;

    /**
     * @var EventStore|MockObject
     */
    private $store;

    /**
     * @var UnitOfWork|MockObject
     */
    private $uow;

    /**
     * @var Event\Listener|MockObject
     */
    private $listener1;

    /**
     * @var Event\Subscription|MockObject
     */
    private $nonEventSourcedSubscription1;

    /**
     * @var Domain\Id|MockObject
     */
    private $id1;

    /**
     * @var Event|MockObject
     */
    private $event1;

    protected function setUp()
    {
        $this->listeners = $this->getMockBuilder(Event\Listener\Factory::class)->getMockForAbstractClass();
        $this->subscriptions = $this->getMockBuilder(Event\Subscription\Factory::class)->getMockForAbstractClass();
        $this->store = $this->getMockBuilder(EventStore::class)->getMockForAbstractClass();
        $this->uow = new UnitOfWork($this->store);

        $this->listener1 = $this->getMockBuilder(Event\Listener::class)->getMockForAbstractClass();

        $this->nonEventSourcedSubscription1 = $this->getMockBuilder(Event\Subscription::class)->getMockForAbstractClass();

        $this->id1 = $this->getMockBuilder(Domain\Id::class)->getMockForAbstractClass();

        $this->event1 = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
    }

    public function testFindingNonEventSourcedSubscription()
    {
        $repository = new EventSourcedRepository($this->listeners, $this->subscriptions, $this->store, $this->uow);

        $this->listeners
            ->expects($this->once())
            ->method('create')
            ->with($this->id1)
            ->willReturn($this->listener1)
        ;

        $this->subscriptions
            ->expects($this->once())
            ->method('create')
            ->with($this->listener1)
            ->willReturn($this->nonEventSourcedSubscription1);

        $exception = new Exception\ObjectNotSupported($this->nonEventSourcedSubscription1);
        $this->expectExceptionObject($exception);

        $repository->find($this->id1);
    }

    public function testFindingNotExistingSubscription()
    {
        $repository = new EventSourcedRepository($this->listeners, $this->subscriptions, $this->store, $this->uow);
        $subscription = new Event\Sourced\Subscription($this->listener1);

        $this->listeners
            ->expects($this->once())
            ->method('create')
            ->with($this->id1)
            ->willReturn($this->listener1)
        ;

        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('id')
            ->willReturn($this->id1)
        ;

        $this->subscriptions
            ->expects($this->once())
            ->method('create')
            ->with($this->listener1)
            ->willReturn($subscription);

        $this->store
            ->expects($this->once())
            ->method('stream')
            ->with($this->id1)
            ->willReturn(new InMemoryStream()) // empty stream
        ;

        $found = $repository->find($this->id1);

        $this->assertNull($found);
        $this->assertFalse($this->uow->has($subscription));
    }

    public function testFindingSubscription()
    {
        $repository = new EventSourcedRepository($this->listeners, $this->subscriptions, $this->store, $this->uow);
        $subscription = new Event\Sourced\Subscription($this->listener1);
        $event = new SubscriptionListenedToEvent($this->event1);

        $this->listeners
            ->expects($this->once())
            ->method('create')
            ->with($this->id1)
            ->willReturn($this->listener1)
        ;

        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('id')
            ->willReturn($this->id1)
        ;

        $this->subscriptions
            ->expects($this->once())
            ->method('create')
            ->with($this->listener1)
            ->willReturn($subscription);

        $this->store
            ->expects($this->once())
            ->method('stream')
            ->with($this->id1)
            ->willReturn(new InMemoryStream($event)) // TODO: mock the stream
        ;

        $found = $repository->find($this->id1);

        $this->assertSame($subscription, $found);
        $this->assertSame($event, $subscription->lastReplayed());
        $this->assertTrue($this->uow->has($subscription));
    }

    public function testAddingNonEventSourcedObject()
    {
        $repository = new EventSourcedRepository($this->listeners, $this->subscriptions, $this->store, $this->uow);

        $exception = new Exception\ObjectNotSupported($this->nonEventSourcedSubscription1);
        $this->expectExceptionObject($exception);

        $repository->add($this->nonEventSourcedSubscription1);
    }
}
