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

namespace Streak\Infrastructure\Domain\Event;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\Event\Exception;
use Streak\Domain\EventBus;
use Streak\Domain\Id;
use Streak\Domain\Id\UUID;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionCompleted;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionListenedToEvent;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\Event\Subscriber
 */
class SubscriberTest extends TestCase
{
    private EventBus|MockObject $bus;

    private Event\Listener\Factory|MockObject $listenerFactory;

    private Event\Subscription\Factory|MockObject $subscriptionFactory;

    private Event\Subscription\Repository|MockObject $subscriptionsRepository;

    private Event\Listener|MockObject $listener1;

    private Event\Subscription|MockObject $subscription1;

    private Event\Envelope $event1;

    protected function setUp(): void
    {
        $this->bus = $this->getMockBuilder(EventBus::class)->getMockForAbstractClass();
        $this->listenerFactory = $this->getMockBuilder(Event\Listener\Factory::class)->getMockForAbstractClass();
        $this->subscriptionFactory = $this->getMockBuilder(Event\Subscription\Factory::class)->getMockForAbstractClass();
        $this->subscriptionsRepository = $this->getMockBuilder(Event\Subscription\Repository::class)->getMockForAbstractClass();
        $this->listener1 = $this->getMockBuilder(Event\Listener::class)->getMockForAbstractClass();
        $this->subscription1 = $this->getMockBuilder(Event\Subscription::class)->getMockForAbstractClass();

        $id1 = $this->getMockBuilder(Id::class)->getMockForAbstractClass();
        $this->event1 = Event\Envelope::new($this->getMockBuilder(Event::class)->getMockForAbstractClass(), $id1);
    }

    public function testSubscriber(): void
    {
        $subscriber = new Subscriber($this->listenerFactory, $this->subscriptionFactory, $this->subscriptionsRepository);
        self::assertInstanceOf(UUID::class, $subscriber->id());
    }

    public function testSubscriberForEventThatSpawnsNoListener(): void
    {
        $subscriber = new Subscriber($this->listenerFactory, $this->subscriptionFactory, $this->subscriptionsRepository);

        $this->listenerFactory
            ->expects(self::once())
            ->method('createFor')
            ->with($this->event1)
            ->willThrowException(new Exception\InvalidEventGiven($this->event1))
        ;

        $processed = $subscriber->on($this->event1);

        self::assertFalse($processed);
    }

    public function testSubscriberForNewListener(): void
    {
        $subscriber = new Subscriber($this->listenerFactory, $this->subscriptionFactory, $this->subscriptionsRepository);

        $this->listenerFactory
            ->expects(self::once())
            ->method('createFor')
            ->with($this->event1)
            ->willReturn($this->listener1)
        ;

        $this->subscriptionFactory
            ->expects(self::once())
            ->method('create')
            ->with($this->listener1)
            ->willReturn($this->subscription1)
        ;

        $this->subscriptionsRepository
            ->expects(self::once())
            ->method('has')
            ->with($this->subscription1)
            ->willReturn(false)
        ;

        $this->subscriptionsRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->subscription1)
        ;

        $processed = $subscriber->on($this->event1);

        self::assertTrue($processed);
    }

    public function testSubscriberForExistingListener(): void
    {
        $subscriber = new Subscriber($this->listenerFactory, $this->subscriptionFactory, $this->subscriptionsRepository);

        $this->listenerFactory
            ->expects(self::once())
            ->method('createFor')
            ->with($this->event1)
            ->willReturn($this->listener1)
        ;

        $this->subscriptionFactory
            ->expects(self::once())
            ->method('create')
            ->with($this->listener1)
            ->willReturn($this->subscription1)
        ;

        $this->subscriptionsRepository
            ->expects(self::once())
            ->method('has')
            ->with($this->subscription1)
            ->willReturn(true)
        ;

        $this->subscriptionsRepository
            ->expects(self::never())
            ->method('add')
        ;

        $processed = $subscriber->on($this->event1);

        self::assertTrue($processed);
    }

    public function testSubscriberForSubscriptionsEvents(): void
    {
        $subscriber = new Subscriber($this->listenerFactory, $this->subscriptionFactory, $this->subscriptionsRepository);

        $this->listenerFactory
            ->expects(self::never())
            ->method('createFor')
        ;

        $this->subscriptionFactory
            ->expects(self::never())
            ->method('create')
        ;

        $event1 = new \Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionStarted($this->event1, new \DateTime());
        $event1 = Event\Envelope::new($event1, UUID::random(), 1);
        $processed = $subscriber->on($event1);

        self::assertFalse($processed);

        $event2 = new SubscriptionListenedToEvent($this->event1, new \DateTime());
        $event2 = Event\Envelope::new($event2, UUID::random(), 2);
        $processed = $subscriber->on($event2);

        self::assertFalse($processed);

        $event3 = new SubscriptionCompleted(new \DateTime());
        $event3 = Event\Envelope::new($event3, UUID::random(), 3);
        $processed = $subscriber->on($event3);

        self::assertFalse($processed);
    }

    public function testListening(): void
    {
        $subscriber = new Subscriber($this->listenerFactory, $this->subscriptionFactory, $this->subscriptionsRepository);

        $this->bus
            ->expects(self::once())
            ->method('add')
            ->with($subscriber)
        ;

        $subscriber->listenTo($this->bus);
    }

    public function committed(Event\Producer ...$producers): \Generator
    {
        foreach ($producers as $producer) {
            yield $producer;
        }
    }
}
