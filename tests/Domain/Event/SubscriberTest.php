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

namespace Streak\Domain\Event;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionCompleted;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionListenedToEvent;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionStarted;
use Streak\Domain\EventBus;
use Streak\Domain\EventStore;
use Streak\Domain\Id\UUID;
use Streak\Infrastructure\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Subscriber
 */
class SubscriberTest extends TestCase
{
    /**
     * @var EventStore|MockObject
     */
    private $store;

    /**
     * @var EventBus|MockObject
     */
    private $bus;

    /**
     * @var Event\Listener\Factory|MockObject
     */
    private $listenerFactory;

    /**
     * @var Event\Subscription\Factory|MockObject
     */
    private $subscriptionFactory;

    /**
     * @var Event\Subscription\Repository|MockObject
     */
    private $subscriptionsRepository;

    /**
     * @var Event\Listener|MockObject
     */
    private $listener1;

    /**
     * @var Event\Subscription|MockObject
     */
    private $subscription1;

    /**
     * @var Event|MockObject
     */
    private $event1;

    /**
     * @var Event\Producer|MockObject
     */
    private $producer1;

    /**
     * @var UnitOfWork|MockObject
     */
    private $uow;

    protected function setUp()
    {
        $this->store = $this->getMockBuilder(EventStore::class)->getMockForAbstractClass();
        $this->bus = $this->getMockBuilder(EventBus::class)->getMockForAbstractClass();
        $this->listenerFactory = $this->getMockBuilder(Event\Listener\Factory::class)->getMockForAbstractClass();
        $this->subscriptionFactory = $this->getMockBuilder(Event\Subscription\Factory::class)->getMockForAbstractClass();
        $this->subscriptionsRepository = $this->getMockBuilder(Event\Subscription\Repository::class)->getMockForAbstractClass();
        $this->listener1 = $this->getMockBuilder(Event\Listener::class)->getMockForAbstractClass();
        $this->subscription1 = $this->getMockBuilder(Event\Subscription::class)->getMockForAbstractClass();
        $this->event1 = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $this->producer1 = $this->getMockBuilder(Event\Producer::class)->getMockForAbstractClass();
        $this->uow = $this->getMockBuilder(UnitOfWork::class)->getMockForAbstractClass();
    }

    public function testSubscriber()
    {
        $subscriber = new Subscriber($this->listenerFactory, $this->subscriptionFactory, $this->subscriptionsRepository, $this->uow);
        $this->assertInstanceOf(UUID::class, $subscriber->id());
    }

    public function testSubscriberForEventThatSpawnsNoListener()
    {
        $subscriber = new Subscriber($this->listenerFactory, $this->subscriptionFactory, $this->subscriptionsRepository, $this->uow);

        $this->listenerFactory
            ->expects($this->once())
            ->method('createFor')
            ->with($this->event1)
            ->willThrowException(new Exception\InvalidEventGiven($this->event1))
        ;

        $this->uow
            ->expects($this->never())
            ->method('commit')
        ;

        $processed = $subscriber->on($this->event1);

        $this->assertFalse($processed);
    }

    public function testSubscriberForNewListener()
    {
        $subscriber = new Subscriber($this->listenerFactory, $this->subscriptionFactory, $this->subscriptionsRepository, $this->uow);

        $this->listenerFactory
            ->expects($this->once())
            ->method('createFor')
            ->with($this->event1)
            ->willReturn($this->listener1)
        ;

        $this->subscriptionFactory
            ->expects($this->once())
            ->method('create')
            ->with($this->listener1)
            ->willReturn($this->subscription1)
        ;

        $this->subscriptionsRepository
            ->expects($this->once())
            ->method('has')
            ->with($this->subscription1)
            ->willReturn(false)
        ;

        $this->subscriptionsRepository
            ->expects($this->once())
            ->method('add')
            ->with($this->subscription1)
        ;

        $this->uow
            ->expects($this->once())
            ->method('commit')
            ->with()
            ->willReturn($this->committed($this->producer1))
        ;

        $processed = $subscriber->on($this->event1);

        $this->assertTrue($processed);
    }

    public function testSubscriberForExistingListener()
    {
        $subscriber = new Subscriber($this->listenerFactory, $this->subscriptionFactory, $this->subscriptionsRepository, $this->uow);

        $this->listenerFactory
            ->expects($this->once())
            ->method('createFor')
            ->with($this->event1)
            ->willReturn($this->listener1)
        ;

        $this->subscriptionFactory
            ->expects($this->once())
            ->method('create')
            ->with($this->listener1)
            ->willReturn($this->subscription1)
        ;

        $this->subscriptionsRepository
            ->expects($this->once())
            ->method('has')
            ->with($this->subscription1)
            ->willReturn(true)
        ;

        $this->subscriptionsRepository
            ->expects($this->never())
            ->method('add')
        ;

        $this->uow
            ->expects($this->never())
            ->method('commit')
        ;

        $processed = $subscriber->on($this->event1);

        $this->assertTrue($processed);
    }

    public function testSubscriberForSubscriptionsEvents()
    {
        $subscriber = new Subscriber($this->listenerFactory, $this->subscriptionFactory, $this->subscriptionsRepository, $this->uow);

        $this->listenerFactory
            ->expects($this->never())
            ->method('createFor')
        ;

        $this->subscriptionFactory
            ->expects($this->never())
            ->method('create')
        ;

        $this->uow
            ->expects($this->never())
            ->method('commit')
        ;

        $processed = $subscriber->on(new SubscriptionStarted($this->event1, new \DateTime()));

        $this->assertFalse($processed);

        $processed = $subscriber->on(new SubscriptionListenedToEvent($this->event1));

        $this->assertFalse($processed);

        $processed = $subscriber->on(new SubscriptionCompleted());

        $this->assertFalse($processed);
    }

    public function testListening()
    {
        $subscriber = new Subscriber($this->listenerFactory, $this->subscriptionFactory, $this->subscriptionsRepository, $this->uow);

        $this->bus
            ->expects($this->once())
            ->method('add')
            ->with($subscriber)
        ;

        $subscriber->listenTo($this->bus);
    }

    public function committed(Event\Producer ...$producers) : \Generator
    {
        foreach ($producers as $producer) {
            yield $producer;
        }
    }
}
