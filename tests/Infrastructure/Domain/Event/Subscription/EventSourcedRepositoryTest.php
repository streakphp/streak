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

namespace Streak\Infrastructure\Domain\Event\Subscription;

use PHPUnit\Framework\TestCase;
use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Domain\Exception;
use Streak\Domain\Id\UUID;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionCompleted;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionListenedToEvent;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionRestarted;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionStarted;
use Streak\Infrastructure\Domain\Event\Subscription\EventSourcedRepositoryTest\DecoratedSubscription;
use Streak\Infrastructure\Domain\Event\Subscription\EventSourcedRepositoryTest\EventSourcedSubscription;
use Streak\Infrastructure\Domain\EventStore\InMemoryEventStore;
use Streak\Infrastructure\Domain\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\Event\Subscription\EventSourcedRepository
 */
class EventSourcedRepositoryTest extends TestCase
{
    private Event\Subscription\Factory $subscriptions;

    private Event\Listener\Factory $listeners;

    private InMemoryEventStore $store;

    private UnitOfWork\EventStoreUnitOfWork $uow;

    private Event\Listener $listener1;

    private EventSourcedSubscription $eventSourcedSubscription1;
    private DecoratedSubscription $eventSourcedSubscription2;
    private DecoratedSubscription $eventSourcedSubscription3;

    private Event\Subscription $nonEventSourcedSubscription1;
    private DecoratedSubscription $nonEventSourcedSubscription2;
    private DecoratedSubscription $nonEventSourcedSubscription3;

    private Listener\Id $id1;
    private Listener\Id $id2;
    private Listener\Id $id3;
    private Listener\Id $id4;

    private Event\Envelope $event1;
    private Event\Envelope $event2;
    private Event\Envelope $event3;
    private Event\Envelope $event4;

    protected function setUp(): void
    {
        $this->subscriptions = $this->getMockBuilder(Event\Subscription\Factory::class)->getMockForAbstractClass();
        $this->listeners = $this->getMockBuilder(Event\Listener\Factory::class)->getMockForAbstractClass();
        $this->store = new InMemoryEventStore();
        $this->uow = new UnitOfWork\EventStoreUnitOfWork($this->store);

        $this->listener1 = $this->getMockBuilder(Event\Listener::class)->setMockClassName('listener1')->getMockForAbstractClass();

        $this->eventSourcedSubscription1 = $this->getMockBuilder(EventSourcedSubscription::class)->setMockClassName('eventSourcedSubscription1')->getMock();
        $this->eventSourcedSubscription2 = $this->getMockBuilder(DecoratedSubscription::class)->setMockClassName('eventSourcedSubscription2')->getMock();
        $this->eventSourcedSubscription2->method('subscription')->willReturn($this->eventSourcedSubscription1);
        $this->eventSourcedSubscription3 = $this->getMockBuilder(DecoratedSubscription::class)->setMockClassName('eventSourcedSubscription3')->getMock();
        $this->eventSourcedSubscription3->method('subscription')->willReturn($this->eventSourcedSubscription2);

        $this->nonEventSourcedSubscription1 = $this->getMockBuilder(Event\Subscription::class)->setMockClassName('nonEventSourcedSubscription1')->getMockForAbstractClass();
        $this->nonEventSourcedSubscription2 = $this->getMockBuilder(DecoratedSubscription::class)->setMockClassName('nonEventSourcedSubscription2')->getMock();
        $this->nonEventSourcedSubscription2->method('subscription')->willReturn($this->nonEventSourcedSubscription1);
        $this->nonEventSourcedSubscription3 = $this->getMockBuilder(DecoratedSubscription::class)->setMockClassName('nonEventSourcedSubscription3')->getMock();
        $this->nonEventSourcedSubscription3->method('subscription')->willReturn($this->nonEventSourcedSubscription2);

        $this->id1 = new class ('f5e65690-e50d-4312-a175-b004ec1bd42a') extends Domain\Id\UUID implements Listener\Id {
        };
        $this->id2 = new class ('d01286b0-7dd6-4520-b714-0e9903ab39af') extends Domain\Id\UUID implements Listener\Id {
        };
        $this->id3 = new class ('39ab6175-7cd7-4c94-95c1-03d05c2e2fa2') extends Domain\Id\UUID implements Listener\Id {
        };
        $this->id4 = new class ('2d40c01d-7aa9-4757-ac28-de3733431cc5') extends Domain\Id\UUID implements Listener\Id {
        };

        $this->event1 = Event\Envelope::new($this->getMockBuilder(Event::class)->setMockClassName('event1')->getMockForAbstractClass(), UUID::random());
        $this->event2 = Event\Envelope::new($this->getMockBuilder(Event::class)->setMockClassName('event2')->getMockForAbstractClass(), UUID::random());
        $this->event3 = Event\Envelope::new($this->getMockBuilder(Event::class)->setMockClassName('event3')->getMockForAbstractClass(), UUID::random());
        $this->event4 = Event\Envelope::new($this->getMockBuilder(Event::class)->setMockClassName('event4')->getMockForAbstractClass(), UUID::random());
    }

    public function testFindingNonEventSourcedSubscription(): void
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);

        $this->listeners
            ->expects(self::once())
            ->method('create')
            ->with($this->id1)
            ->willReturn($this->listener1)
        ;

        $this->subscriptions
            ->expects(self::once())
            ->method('create')
            ->with($this->listener1)
            ->willReturn($this->nonEventSourcedSubscription1)
        ;

        $exception = new Exception\ObjectNotSupported($this->nonEventSourcedSubscription1);
        $this->expectExceptionObject($exception);

        $repository->find($this->id1);
    }

    public function testCheckingForNonEventSourcedSubscription(): void
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);

        $exception = new Exception\ObjectNotSupported($this->nonEventSourcedSubscription3);
        $this->expectExceptionObject($exception);

        $repository->has($this->nonEventSourcedSubscription3);
    }

    public function testFindingNotExistingSubscription(): void
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);

        $this->listeners
            ->expects(self::once())
            ->method('create')
            ->with($this->id1)
            ->willReturn($this->listener1)
        ;

        $this->subscriptions
            ->expects(self::once())
            ->method('create')
            ->with($this->listener1)
            ->willReturn($this->eventSourcedSubscription1)
        ;

        $this->eventSourcedSubscription1
            ->expects(self::once())
            ->method('id')
            ->willReturn($this->id1)
        ;

        $found = $repository->find($this->id1);

        self::assertNull($found);
    }

    public function testCheckingForExistingSubscription(): void
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);

        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $event1 = new SubscriptionStarted($this->event1, $now);
        $event1 = Event\Envelope::new($event1, $this->id1, 1);
        $event2 = new SubscriptionListenedToEvent($this->event1, $now);
        $event2 = Event\Envelope::new($event2, $this->id1, 2);

        $this->store->add($event1, $event2);

        $this->eventSourcedSubscription1
            ->expects(self::once())
            ->method('id')
            ->willReturn($this->id1)
        ;

        $has = $repository->has($this->eventSourcedSubscription3);

        self::assertTrue($has);
    }

    public function testCheckingForNotExistingSubscription(): void
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);

        $this->eventSourcedSubscription1
            ->expects(self::once())
            ->method('id')
            ->willReturn($this->id1)
        ;

        $has = $repository->has($this->eventSourcedSubscription3);

        self::assertFalse($has);
    }

    public function testFindingSubscription(): void
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);

        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $event1 = new SubscriptionStarted($this->event1, $now);
        $event1 = Event\Envelope::new($event1, $this->id1, 1);
        $event2 = new SubscriptionListenedToEvent($this->event1, $now);
        $event2 = Event\Envelope::new($event2, $this->id1, 2);
        $event3 = new SubscriptionListenedToEvent($this->event2, $now);
        $event3 = Event\Envelope::new($event3, $this->id1, 3);

        $this->store->add($event1, $event2, $event3);

        $this->listeners
            ->expects(self::once())
            ->method('create')
            ->with($this->id1)
            ->willReturn($this->listener1)
        ;

        $this->subscriptions
            ->expects(self::once())
            ->method('create')
            ->with($this->listener1)
            ->willReturn($this->eventSourcedSubscription1)
        ;

        $this->eventSourcedSubscription1
            ->expects(self::once())
            ->method('id')
            ->willReturn($this->id1)
        ;

        $this->eventSourcedSubscription1
            ->expects(self::once())
            ->method('replay')
            ->with(self::callback(function (Event\Stream $stream) use ($event1, $event2, $event3) {
                $stream = iterator_to_array($stream);

                return self::equalTo([$event1, $event2, $event3])->evaluate($stream);
            }))
        ;

        $subscription = $repository->find($this->id1);

        self::assertSame($this->eventSourcedSubscription1, $subscription);
    }

    public function testFindingPreviouslyRestartedSubscription(): void
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);

        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $event1 = new SubscriptionStarted($this->event1, $now);
        $event1 = Event\Envelope::new($event1, $this->id1, 1);
        $event2 = new SubscriptionListenedToEvent($this->event1, $now);
        $event2 = Event\Envelope::new($event2, $this->id1, 2);
        $event3 = new SubscriptionListenedToEvent($this->event2, $now);
        $event3 = Event\Envelope::new($event3, $this->id1, 3);
        $event4 = new SubscriptionRestarted($this->event1, $now);
        $event4 = Event\Envelope::new($event4, $this->id1, 4);
        $event5 = new SubscriptionListenedToEvent($this->event1, $now);
        $event5 = Event\Envelope::new($event5, $this->id1, 5);
        $event6 = new SubscriptionListenedToEvent($this->event3, $now);
        $event6 = Event\Envelope::new($event6, $this->id1, 6);

        $this->store->add($event1, $event2, $event3, $event4, $event5, $event6);

        $this->listeners
            ->expects(self::once())
            ->method('create')
            ->with($this->id1)
            ->willReturn($this->listener1)
        ;

        $this->subscriptions
            ->expects(self::once())
            ->method('create')
            ->with($this->listener1)
            ->willReturn($this->eventSourcedSubscription1)
        ;

        $this->eventSourcedSubscription1
            ->expects(self::once())
            ->method('id')
            ->willReturn($this->id1)
        ;

        $this->eventSourcedSubscription1
            ->expects(self::once())
            ->method('replay')
            ->with(self::callback(function (Event\Stream $stream) use ($event1, $event2, $event3, $event4, $event5, $event6) {
                $stream = iterator_to_array($stream);

                // streaming from SubscriptionRestarted event
                return self::equalTo([$event1, $event2, $event3, $event4, $event5, $event6])->evaluate($stream);
            }))
        ;

        $subscription = $repository->find($this->id1);

        self::assertSame($this->eventSourcedSubscription1, $subscription);
    }

    public function testAddingNonEventSourcedObject(): void
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);

        $exception = new Exception\ObjectNotSupported($this->nonEventSourcedSubscription3);
        $this->expectExceptionObject($exception);

        $repository->add($this->nonEventSourcedSubscription3);
    }

    public function testAddingEventSourcedSubscription(): void
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);

        $this->eventSourcedSubscription1
            ->expects(self::atLeastOnce())
            ->method('id')
            ->willReturn($this->id1)
        ;

        $repository->add($this->eventSourcedSubscription1);

        self::assertTrue($this->uow->has($this->eventSourcedSubscription1));
    }

    public function testAddingDecoratedEventSourcedSubscription(): void
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);

        $this->eventSourcedSubscription1
            ->expects(self::atLeastOnce())
            ->method('id')
            ->willReturn($this->id1)
        ;

        $repository->add($this->eventSourcedSubscription3);

        self::assertTrue($this->uow->has($this->eventSourcedSubscription1));
    }

    public function testRepositoryOfNoSubscriptions(): void
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);

        $subscriptions = $repository->all();

        self::assertIsIterable($subscriptions);
        self::assertEquals([], iterator_to_array($subscriptions));
    }

    public function testFindingSubscriptions(): void
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);
        $subscription1 = new LazyLoadedSubscription($this->id1, $repository);
        $subscription3 = new LazyLoadedSubscription($this->id3, $repository);
        $subscription4 = new LazyLoadedSubscription($this->id4, $repository);

        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listeners
            ->expects(self::never())
            ->method(self::anything())
        ;
        $this->subscriptions
            ->expects(self::never())
            ->method(self::anything())
        ;

        $event11 = new SubscriptionStarted($this->event1, $now);
        $event11 = Event\Envelope::new($event11, $this->id1, 1);
        $event21 = new SubscriptionStarted($this->event2, $now);
        $event21 = Event\Envelope::new($event21, $this->id2, 1);
        $event12 = new SubscriptionCompleted($now);
        $event12 = Event\Envelope::new($event12, $this->id1, 2);
        $event31 = new SubscriptionStarted($this->event3, $now);
        $event31 = Event\Envelope::new($event31, $this->id3, 1);
        $event22 = new SubscriptionCompleted($now);
        $event22 = Event\Envelope::new($event22, $this->id2, 2);
        $event13 = new SubscriptionRestarted($this->event4, $now);
        $event13 = Event\Envelope::new($event13, $this->id1, 3);
        $event41 = new SubscriptionStarted($this->event1, $now);
        $event41 = Event\Envelope::new($event41, $this->id4, 1);
        $event42 = new SubscriptionRestarted($this->event2, $now);
        $event42 = Event\Envelope::new($event42, $this->id4, 2);

        $this->store->add($event11);
        $this->store->add($event21);
        $this->store->add($event12);
        $this->store->add($event31);
        $this->store->add($event22);
        $this->store->add($event13);
        $this->store->add($event41);
        $this->store->add($event42);

        $subscriptions = $repository->all();
        $subscriptions = iterator_to_array($subscriptions);

        self::assertEquals([$subscription3, $subscription1, $subscription4], $subscriptions);
    }
}

namespace Streak\Infrastructure\Domain\Event\Subscription\EventSourcedRepositoryTest;

use Streak\Domain\Event;

abstract class EventSourcedSubscription implements Event\Sourced\Subscription
{
}

abstract class DecoratedSubscription implements Event\Subscription, Event\Subscription\Decorator
{
}
