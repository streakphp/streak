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
use Streak\Domain\Id;
use Streak\Domain\Id\UUID;

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

    protected function setUp() : void
    {
        $this->store = $this->getMockBuilder(EventStore::class)->getMockForAbstractClass();
        $this->bus = $this->getMockBuilder(EventBus::class)->getMockForAbstractClass();
        $this->listenerFactory = $this->getMockBuilder(Event\Listener\Factory::class)->getMockForAbstractClass();
        $this->subscriptionFactory = $this->getMockBuilder(Event\Subscription\Factory::class)->getMockForAbstractClass();
        $this->subscriptionsRepository = $this->getMockBuilder(Event\Subscription\Repository::class)->getMockForAbstractClass();
        $this->listener1 = $this->getMockBuilder(Event\Listener::class)->getMockForAbstractClass();
        $this->subscription1 = $this->getMockBuilder(Event\Subscription::class)->getMockForAbstractClass();
        $this->producer1 = $this->getMockBuilder(Event\Producer::class)->getMockForAbstractClass();

        $id1 = $this->getMockBuilder(Id::class)->getMockForAbstractClass();
        $this->event1 = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $this->event1 = Event\Envelope::new($this->event1, $id1);
    }

    public function testSubscriber()
    {
        $subscriber = new Subscriber($this->listenerFactory, $this->subscriptionFactory, $this->subscriptionsRepository);
        $this->assertInstanceOf(UUID::class, $subscriber->id());
    }

    public function testSubscriberForEventThatSpawnsNoListener()
    {
        $subscriber = new Subscriber($this->listenerFactory, $this->subscriptionFactory, $this->subscriptionsRepository);

        $this->listenerFactory
            ->expects($this->once())
            ->method('createFor')
            ->with($this->event1)
            ->willThrowException(new Exception\InvalidEventGiven($this->event1))
        ;

        $processed = $subscriber->on($this->event1);

        $this->assertFalse($processed);
    }

    public function testSubscriberForNewListener()
    {
        $subscriber = new Subscriber($this->listenerFactory, $this->subscriptionFactory, $this->subscriptionsRepository);

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

        $processed = $subscriber->on($this->event1);

        $this->assertTrue($processed);
    }

    public function testSubscriberForExistingListener()
    {
        $subscriber = new Subscriber($this->listenerFactory, $this->subscriptionFactory, $this->subscriptionsRepository);

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

        $processed = $subscriber->on($this->event1);

        $this->assertTrue($processed);
    }

    public function testSubscriberForSubscriptionsEvents()
    {
        $subscriber = new Subscriber($this->listenerFactory, $this->subscriptionFactory, $this->subscriptionsRepository);

        $this->listenerFactory
            ->expects($this->never())
            ->method('createFor')
        ;

        $this->subscriptionFactory
            ->expects($this->never())
            ->method('create')
        ;

        $event1 = new SubscriptionStarted($this->event1, new \DateTime());
        $event1 = Event\Envelope::new($event1, UUID::random(), 1);
        $processed = $subscriber->on($event1);

        $this->assertFalse($processed);

        $event2 = new SubscriptionListenedToEvent($this->event1, new \DateTime());
        $event2 = Event\Envelope::new($event2, UUID::random(), 2);
        $processed = $subscriber->on($event2);

        $this->assertFalse($processed);

        $event3 = new SubscriptionCompleted(new \DateTime());
        $event3 = Event\Envelope::new($event3, UUID::random(), 3);
        $processed = $subscriber->on($event3);

        $this->assertFalse($processed);
    }

    public function testListening()
    {
        $subscriber = new Subscriber($this->listenerFactory, $this->subscriptionFactory, $this->subscriptionsRepository);

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
