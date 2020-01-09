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
use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionCompleted;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionListenedToEvent;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionRestarted;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionStarted;
use Streak\Domain\EventStore;
use Streak\Domain\Exception;
use Streak\Domain\Id\UUID;
use Streak\Infrastructure\Event\Sourced\Subscription;
use Streak\Infrastructure\EventStore\InMemoryEventStore;
use Streak\Infrastructure\FixedClock;
use Streak\Infrastructure\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Event\Subscription\EventSourcedRepository
 */
class EventSourcedRepositoryTest extends TestCase
{
    /**
     * @var Event\Subscription\Factory|MockObject
     */
    private $subscriptions;

    /**
     * @var Event\Listener\Factory|MockObject
     */
    private $listeners;

    /**
     * @var EventStore|MockObject
     */
    private $store;

    /**
     * @var UnitOfWork\EventStoreUnitOfWork
     */
    private $uow;

    /**
     * @var Event\Listener|MockObject
     */
    private $listener1;

    /**
     * @var Event\Subscription|Event\Sourced|MockObject
     */
    private $eventSourcedSubscription1;

    /**
     * @var Event\Subscription|Event\Subscription\Decorator|MockObject
     */
    private $eventSourcedSubscription2;

    /**
     * @var Event\Subscription|Event\Subscription\Decorator|MockObject
     */
    private $eventSourcedSubscription3;

    /**
     * @var Event\Subscription|MockObject
     */
    private $nonEventSourcedSubscription1;

    /**
     * @var Event\Subscription|Event\Subscription\Decorator|MockObject
     */
    private $nonEventSourcedSubscription2;

    /**
     * @var Event\Subscription|Event\Subscription\Decorator|MockObject
     */
    private $nonEventSourcedSubscription3;

    /**
     * @var Listener\Id|MockObject
     */
    private $id1;

    /**
     * @var Listener\Id|MockObject
     */
    private $id2;

    /**
     * @var Listener\Id|MockObject
     */
    private $id3;

    /**
     * @var Listener\Id|MockObject
     */
    private $id4;

    /**
     * @var Event|MockObject
     */
    private $event1;

    /**
     * @var Event|MockObject
     */
    private $event2;

    /**
     * @var Event|MockObject
     */
    private $event3;

    /**
     * @var Event|MockObject
     */
    private $event4;

    /**
     * @var FixedClock
     */
    private $clock;

    protected function setUp()
    {
        $this->subscriptions = $this->getMockBuilder(Event\Subscription\Factory::class)->getMockForAbstractClass();
        $this->listeners = $this->getMockBuilder(Event\Listener\Factory::class)->getMockForAbstractClass();
        $this->store = new InMemoryEventStore();
        $this->uow = new UnitOfWork\EventStoreUnitOfWork($this->store);

        $this->listener1 = $this->getMockBuilder(Event\Listener::class)->setMockClassName('listener1')->getMockForAbstractClass();

        $this->eventSourcedSubscription1 = $this->getMockBuilder([Event\Subscription::class, Event\Sourced::class])->setMockClassName('eventSourcedSubscription1')->getMock();
        $this->eventSourcedSubscription2 = $this->getMockBuilder([Event\Subscription::class, Event\Subscription\Decorator::class])->setMockClassName('eventSourcedSubscription2')->getMock();
        $this->eventSourcedSubscription2->expects($this->any())->method('subscription')->willReturn($this->eventSourcedSubscription1);
        $this->eventSourcedSubscription3 = $this->getMockBuilder([Event\Subscription::class, Event\Subscription\Decorator::class])->setMockClassName('eventSourcedSubscription3')->getMock();
        $this->eventSourcedSubscription3->expects($this->any())->method('subscription')->willReturn($this->eventSourcedSubscription2);

        $this->nonEventSourcedSubscription1 = $this->getMockBuilder(Event\Subscription::class)->setMockClassName('nonEventSourcedSubscription1')->getMockForAbstractClass();
        $this->nonEventSourcedSubscription2 = $this->getMockBuilder([Event\Subscription::class, Event\Subscription\Decorator::class])->setMockClassName('nonEventSourcedSubscription2')->getMock();
        $this->nonEventSourcedSubscription2->expects($this->any())->method('subscription')->willReturn($this->nonEventSourcedSubscription1);
        $this->nonEventSourcedSubscription3 = $this->getMockBuilder([Event\Subscription::class, Event\Subscription\Decorator::class])->setMockClassName('nonEventSourcedSubscription3')->getMock();
        $this->nonEventSourcedSubscription3->expects($this->any())->method('subscription')->willReturn($this->nonEventSourcedSubscription2);

        $this->id1 = new class('f5e65690-e50d-4312-a175-b004ec1bd42a') extends Domain\Id\UUID implements Listener\Id {
        };
        $this->id2 = new class('d01286b0-7dd6-4520-b714-0e9903ab39af') extends Domain\Id\UUID implements Listener\Id {
        };
        $this->id3 = new class('39ab6175-7cd7-4c94-95c1-03d05c2e2fa2') extends Domain\Id\UUID implements Listener\Id {
        };
        $this->id4 = new class('2d40c01d-7aa9-4757-ac28-de3733431cc5') extends Domain\Id\UUID implements Listener\Id {
        };

        $this->event1 = $this->getMockBuilder(Event::class)->setMockClassName('event1')->getMockForAbstractClass();
        $this->event1 = Event\Envelope::new($this->event1, UUID::random());
        $this->event2 = $this->getMockBuilder(Event::class)->setMockClassName('event2')->getMockForAbstractClass();
        $this->event2 = Event\Envelope::new($this->event2, UUID::random());
        $this->event3 = $this->getMockBuilder(Event::class)->setMockClassName('event3')->getMockForAbstractClass();
        $this->event3 = Event\Envelope::new($this->event3, UUID::random());
        $this->event4 = $this->getMockBuilder(Event::class)->setMockClassName('event4')->getMockForAbstractClass();
        $this->event4 = Event\Envelope::new($this->event4, UUID::random());

        $this->clock = new FixedClock(new \DateTime('2018-09-28 19:12:32.763188 +00:00'));
    }

    public function testFindingNonEventSourcedSubscription()
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);

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

    public function testCheckingForNonEventSourcedSubscription()
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);

        $exception = new Exception\ObjectNotSupported($this->nonEventSourcedSubscription3);
        $this->expectExceptionObject($exception);

        $repository->has($this->nonEventSourcedSubscription3);
    }

    public function testFindingNotExistingSubscription()
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);

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
            ->willReturn($this->eventSourcedSubscription1)
        ;

        $this->eventSourcedSubscription1
            ->expects($this->once())
            ->method('producerId')
            ->willReturn($this->id1)
        ;

        $found = $repository->find($this->id1);

        $this->assertNull($found);
    }

    public function testCheckingForExistingSubscription()
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);

        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $event1 = new SubscriptionStarted($this->event1, $now);
        $event1 = Event\Envelope::new($event1, $this->id1, 1);
        $event2 = new SubscriptionListenedToEvent($this->event1, $now);
        $event2 = Event\Envelope::new($event2, $this->id1, 2);

        $this->store->add($event1, $event2);

        $this->eventSourcedSubscription1
            ->expects($this->once())
            ->method('producerId')
            ->willReturn($this->id1)
        ;

        $has = $repository->has($this->eventSourcedSubscription3);

        $this->assertTrue($has);
    }

    public function testCheckingForNotExistingSubscription()
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);

        $this->eventSourcedSubscription1
            ->expects($this->once())
            ->method('producerId')
            ->willReturn($this->id1)
        ;

        $has = $repository->has($this->eventSourcedSubscription3);

        $this->assertFalse($has);
    }

    public function testFindingSubscription()
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
            ->expects($this->once())
            ->method('create')
            ->with($this->id1)
            ->willReturn($this->listener1)
        ;

        $this->subscriptions
            ->expects($this->once())
            ->method('create')
            ->with($this->listener1)
            ->willReturn($this->eventSourcedSubscription1)
        ;

        $this->eventSourcedSubscription1
            ->expects($this->once())
            ->method('producerId')
            ->willReturn($this->id1)
        ;

        $this->eventSourcedSubscription1
            ->expects($this->once())
            ->method('replay')
            ->with($this->callback(function (Event\Stream $stream) use ($event1, $event2, $event3) {
                $stream = iterator_to_array($stream);

                return $this->equalTo([$event1, $event2, $event3])->evaluate($stream);
            }))
        ;

        $subscription = $repository->find($this->id1);

        $this->assertSame($this->eventSourcedSubscription1, $subscription);
    }

    public function testFindingPreviouslyRestartedSubscription()
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
            ->expects($this->once())
            ->method('create')
            ->with($this->id1)
            ->willReturn($this->listener1)
        ;

        $this->subscriptions
            ->expects($this->once())
            ->method('create')
            ->with($this->listener1)
            ->willReturn($this->eventSourcedSubscription1)
        ;

        $this->eventSourcedSubscription1
            ->expects($this->once())
            ->method('producerId')
            ->willReturn($this->id1)
        ;

        $this->eventSourcedSubscription1
            ->expects($this->once())
            ->method('replay')
            ->with($this->callback(function (Event\Stream $stream) use ($event1, $event2, $event3, $event4, $event5, $event6) {
                $stream = iterator_to_array($stream);

                // streaming from SubscriptionRestarted event
                return $this->equalTo([$event1, $event2, $event3, $event4, $event5, $event6])->evaluate($stream);
            }))
        ;

        $subscription = $repository->find($this->id1);

        $this->assertSame($this->eventSourcedSubscription1, $subscription);
    }

    public function testCheckingForSubscriptionStillInMemory()
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);
        $subscription = new Subscription($this->listener1, $this->clock);

        $this->uow->add($subscription);

        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;

        $has = $repository->has($subscription);

        $this->assertTrue($has);
    }

    public function testAddingNonEventSourcedObject()
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);

        $exception = new Exception\ObjectNotSupported($this->nonEventSourcedSubscription3);
        $this->expectExceptionObject($exception);

        $repository->add($this->nonEventSourcedSubscription3);
    }

    public function testAddingEventSourcedSubscription()
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);

        $this->eventSourcedSubscription1
            ->expects($this->atLeastOnce())
            ->method('producerId')
            ->willReturn($this->id1)
        ;

        $repository->add($this->eventSourcedSubscription1);

        $this->assertTrue($this->uow->has($this->eventSourcedSubscription1));
    }

    public function testAddingDecoratedEventSourcedSubscription()
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);

        $this->eventSourcedSubscription1
            ->expects($this->atLeastOnce())
            ->method('producerId')
            ->willReturn($this->id1)
        ;

        $repository->add($this->eventSourcedSubscription3);

        $this->assertTrue($this->uow->has($this->eventSourcedSubscription1));
    }

    public function testRepositoryOfNoSubscriptions()
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);

        $subscriptions = $repository->all();

        $this->assertTrue(is_iterable($subscriptions));
        $this->assertEquals([], iterator_to_array($subscriptions));
    }

    public function testFindingSubscriptions()
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);
        $subscription1 = new LazyLoadedSubscription($this->id1, $repository);
        $subscription3 = new LazyLoadedSubscription($this->id3, $repository);
        $subscription4 = new LazyLoadedSubscription($this->id4, $repository);

        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listeners
            ->expects($this->never())
            ->method($this->anything())
        ;
        $this->subscriptions
            ->expects($this->never())
            ->method($this->anything())
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

        $this->assertEquals([$subscription3, $subscription1, $subscription4], $subscriptions);
    }
}
