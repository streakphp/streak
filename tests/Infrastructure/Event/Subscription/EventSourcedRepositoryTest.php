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
     * @var UnitOfWork|MockObject
     */
    private $uow;

    /**
     * @var Event\Listener|MockObject
     */
    private $listener1;

    /**
     * @var Event\Listener|MockObject
     */
    private $listener2;

    /**
     * @var Event\Listener|MockObject
     */
    private $listener3;

    /**
     * @var Event\Listener|MockObject
     */
    private $listener4;

    /**
     * @var Event\Subscription|MockObject
     */
    private $nonEventSourcedSubscription1;

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
     * @var Event\Stream|MockObject
     */
    private $all;

    /**
     * @var Event\Stream|MockObject
     */
    private $stream1;

    /**
     * @var Event\Stream|MockObject
     */
    private $stream2;

    /**
     * @var Event\Stream|MockObject
     */
    private $stream3;

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
        $this->listener2 = $this->getMockBuilder(Event\Listener::class)->setMockClassName('listener2')->getMockForAbstractClass();
        $this->listener3 = $this->getMockBuilder(Event\Listener::class)->setMockClassName('listener3')->getMockForAbstractClass();
        $this->listener4 = $this->getMockBuilder(Event\Listener::class)->setMockClassName('listener4')->getMockForAbstractClass();

        $this->nonEventSourcedSubscription1 = $this->getMockBuilder(Event\Subscription::class)->getMockForAbstractClass();

        $this->id1 = new class('f5e65690-e50d-4312-a175-b004ec1bd42a') extends Domain\Id\UUID implements Listener\Id {
        };
        $this->id2 = new class('d01286b0-7dd6-4520-b714-0e9903ab39af') extends Domain\Id\UUID implements Listener\Id {
        };
        $this->id3 = new class('39ab6175-7cd7-4c94-95c1-03d05c2e2fa2') extends Domain\Id\UUID implements Listener\Id {
        };
        $this->id4 = new class('2d40c01d-7aa9-4757-ac28-de3733431cc5') extends Domain\Id\UUID implements Listener\Id {
        };

        $this->event1 = $this->getMockBuilder(Event::class)->setMockClassName('event1')->getMockForAbstractClass();
        $this->event2 = $this->getMockBuilder(Event::class)->setMockClassName('event2')->getMockForAbstractClass();
        $this->event3 = $this->getMockBuilder(Event::class)->setMockClassName('event3')->getMockForAbstractClass();
        $this->event4 = $this->getMockBuilder(Event::class)->setMockClassName('event4')->getMockForAbstractClass();

        $this->all = $this->getMockBuilder(IteratorAggregateStream::class)->setMockClassName('all')->getMockForAbstractClass();
        $this->stream1 = $this->getMockBuilder(IteratorAggregateStream::class)->setMockClassName('stream1')->getMockForAbstractClass();
        $this->stream2 = $this->getMockBuilder(IteratorAggregateStream::class)->setMockClassName('stream2')->getMockForAbstractClass();
        $this->stream3 = $this->getMockBuilder(IteratorAggregateStream::class)->setMockClassName('stream3')->getMockForAbstractClass();

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

        $exception = new Exception\ObjectNotSupported($this->nonEventSourcedSubscription1);
        $this->expectExceptionObject($exception);

        $repository->has($this->nonEventSourcedSubscription1);
    }

    public function testFindingNotExistingSubscription()
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);
        $subscription = new Event\Sourced\Subscription($this->listener1, $this->clock);

        $this->listeners
            ->expects($this->once())
            ->method('create')
            ->with($this->id1)
            ->willReturn($this->listener1)
        ;

        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;

        $this->subscriptions
            ->expects($this->once())
            ->method('create')
            ->with($this->listener1)
            ->willReturn($subscription)
        ;

        $found = $repository->find($this->id1);

        $this->assertNull($found);
    }

    public function testCheckingForNotExistingSubscription()
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);
        $subscription = new Event\Sourced\Subscription($this->listener1, $this->clock);

        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;

        $has = $repository->has($subscription);

        $this->assertFalse($has);
    }

    public function testFindingSubscription()
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);
        $subscription = new Event\Sourced\Subscription($this->listener1, $this->clock);

        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $event1 = new SubscriptionStarted($this->event1, $now);
        $event2 = new SubscriptionListenedToEvent($this->event1, 2, $now);

        $this->store->add($this->id1, 0, ...[$event1, $event2]);

        $this->listeners
            ->expects($this->once())
            ->method('create')
            ->with($this->id1)
            ->willReturn($this->listener1)
        ;

        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;

        $this->subscriptions
            ->expects($this->once())
            ->method('create')
            ->with($this->listener1)
            ->willReturn($subscription)
        ;

        $found = $repository->find($this->id1);

        $this->assertSame($subscription, $found);
        $this->assertSame($event2, $subscription->lastReplayed());
    }

    public function testCheckingForSubscription()
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);
        $subscription = new Event\Sourced\Subscription($this->listener1, $this->clock);

        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $event1 = new SubscriptionStarted($this->event1, $now);
        $event2 = new SubscriptionListenedToEvent($this->event1, 2, $now);

        $this->store->add($this->id1, 0, ...[$event1, $event2]);

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

        $exception = new Exception\ObjectNotSupported($this->nonEventSourcedSubscription1);
        $this->expectExceptionObject($exception);

        $repository->add($this->nonEventSourcedSubscription1);
    }

    public function testAddingEventSourcedSubscription()
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);
        $subscription = new Event\Sourced\Subscription($this->listener1, $this->clock);

        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;

        $repository->add($subscription);

        $this->assertTrue($this->uow->has($subscription));
    }

    public function testRepositoryOfNoSubscriptions()
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);

        $subscriptions = $repository->all();

        $this->assertTrue(is_iterable($subscriptions));
        $this->assertEquals([], iterator_to_array($subscriptions));
    }

    public function testRepositoryOfVariousSubscriptions()
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);
        $subscription1 = new Event\Sourced\Subscription($this->listener1, $this->clock);
        $subscription3 = new Event\Sourced\Subscription($this->listener3, $this->clock);

        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listeners
            ->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                [$this->id3],
                [$this->id1]
            )->willReturnOnConsecutiveCalls(
                $this->listener3,
                $this->listener1
            )
        ;
        $this->listener3
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id3)
        ;
        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $this->subscriptions
            ->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                [$this->listener3],
                [$this->listener1]
            )->willReturnOnConsecutiveCalls(
                $subscription3,
                $subscription1
            )
        ;

        $event11 = new SubscriptionStarted($this->event1, $now);
        $event21 = new SubscriptionStarted($this->event2, $now);
        $event12 = new SubscriptionCompleted(2, $now);
        $event31 = new SubscriptionStarted($this->event3, $now);
        $event22 = new SubscriptionCompleted(2, $now);
        $event13 = new SubscriptionRestarted($this->event4, 3, $now);

        $this->store->add($this->id1, 0, $event11);
        $this->store->add($this->id2, 0, $event21);
        $this->store->add($this->id1, 1, $event12);
        $this->store->add($this->id3, 1, $event31);
        $this->store->add($this->id2, 1, $event22);
        $this->store->add($this->id1, 2, $event13);

        $subscriptions = $repository->all();
        $subscriptions = iterator_to_array($subscriptions);

        $this->assertEquals([$subscription3, $subscription1], $subscriptions);
    }

    private function isIteratorFor(MockObject $iterator, array $items)
    {
        $internal = new \ArrayIterator($items);

        $iterator
            ->expects($this->any())
            ->method('getIterator')
            ->willReturn($internal)
        ;

        return $iterator;
    }
}

interface IteratorAggregateStream extends Event\Stream, \IteratorAggregate
{
}
