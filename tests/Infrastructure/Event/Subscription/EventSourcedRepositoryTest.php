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
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionCompleted;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionListenedToEvent;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionStarted;
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
    private $subscription1;

    /**
     * @var Event\Subscription|MockObject
     */
    private $subscription2;

    /**
     * @var Event\Subscription|MockObject
     */
    private $subscription3;

    /**
     * @var Event\Subscription|MockObject
     */
    private $subscription4;

    /**
     * @var Event\Subscription|MockObject
     */
    private $nonEventSourcedSubscription1;

    /**
     * @var Domain\Id|MockObject
     */
    private $id1;

    /**
     * @var Domain\Id|MockObject
     */
    private $id2;

    /**
     * @var Domain\Id|MockObject
     */
    private $id3;

    /**
     * @var Domain\Id|MockObject
     */
    private $id4;

    /**
     * @var Event|MockObject
     */
    private $event1;

    /**
     * @var Event\FilterableStream|MockObject
     */
    private $stream1;

    /**
     * @var Event\FilterableStream|MockObject
     */
    private $stream2;

    /**
     * @var Event\FilterableStream|MockObject
     */
    private $stream3;

    protected function setUp()
    {
        $this->subscriptions = $this->getMockBuilder(Event\Subscription\Factory::class)->getMockForAbstractClass();
        $this->listeners = $this->getMockBuilder(Event\Listener\Factory::class)->getMockForAbstractClass();
        $this->store = $this->getMockBuilder(EventStore::class)->getMockForAbstractClass();
        $this->uow = new UnitOfWork($this->store);

        $this->listener1 = $this->getMockBuilder(Event\Listener::class)->setMockClassName('listener1')->getMockForAbstractClass();
        $this->listener2 = $this->getMockBuilder(Event\Listener::class)->setMockClassName('listener2')->getMockForAbstractClass();
        $this->listener3 = $this->getMockBuilder(Event\Listener::class)->setMockClassName('listener3')->getMockForAbstractClass();
        $this->listener4 = $this->getMockBuilder(Event\Listener::class)->setMockClassName('listener4')->getMockForAbstractClass();

        $this->subscription1 = $this->getMockBuilder(Event\Subscription::class)->getMockForAbstractClass();
        $this->subscription2 = $this->getMockBuilder(Event\Subscription::class)->getMockForAbstractClass();
        $this->subscription3 = $this->getMockBuilder(Event\Subscription::class)->getMockForAbstractClass();
        $this->subscription4 = $this->getMockBuilder(Event\Subscription::class)->getMockForAbstractClass();

        $this->nonEventSourcedSubscription1 = $this->getMockBuilder(Event\Subscription::class)->getMockForAbstractClass();

        $this->id1 = $this->getMockBuilder(Domain\Id::class)->getMockForAbstractClass();
        $this->id2 = $this->getMockBuilder(Domain\Id::class)->getMockForAbstractClass();
        $this->id3 = $this->getMockBuilder(Domain\Id::class)->getMockForAbstractClass();
        $this->id4 = $this->getMockBuilder(Domain\Id::class)->getMockForAbstractClass();

        $this->event1 = $this->getMockBuilder(Event::class)->getMockForAbstractClass();

        $this->stream1 = $this->getMockBuilder(Event\FilterableStream::class)->getMockForAbstractClass();
        $this->stream2 = $this->getMockBuilder(Event\FilterableStream::class)->getMockForAbstractClass();
        $this->stream3 = $this->getMockBuilder(Event\FilterableStream::class)->getMockForAbstractClass();
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
            ->willReturn($subscription)
        ;

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

    public function testCheckingForNotExistingSubscription()
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);
        $subscription = new Event\Sourced\Subscription($this->listener1);

        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('id')
            ->willReturn($this->id1)
        ;

        $this->store
            ->expects($this->once())
            ->method('stream')
            ->with($this->id1)
            ->willReturn(new InMemoryStream()) // empty stream
        ;

        $has = $repository->has($subscription);

        $this->assertFalse($has);
    }

    public function testFindingSubscription()
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);
        $subscription = new Event\Sourced\Subscription($this->listener1);
        $event1 = new SubscriptionStarted($this->event1, new \DateTime());
        $event2 = new SubscriptionListenedToEvent($this->event1);

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
            ->willReturn($subscription)
        ;

        $this->store
            ->expects($this->once())
            ->method('stream')
            ->with($this->id1)
            ->willReturn(new InMemoryStream($event1, $event2)) // TODO: mock the stream
        ;

        $found = $repository->find($this->id1);

        $this->assertSame($subscription, $found);
        $this->assertSame($event2, $subscription->lastReplayed());
        $this->assertTrue($this->uow->has($subscription));
    }

    public function testCheckingForSubscription()
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);
        $subscription = new Event\Sourced\Subscription($this->listener1);
        $event1 = new SubscriptionStarted($this->event1, new \DateTime());
        $event2 = new SubscriptionListenedToEvent($this->event1);

        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('id')
            ->willReturn($this->id1)
        ;

        $this->store
            ->expects($this->once())
            ->method('stream')
            ->with($this->id1)
            ->willReturn(new InMemoryStream($event1, $event2)) // TODO: mock the stream
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
        $subscription = new Event\Sourced\Subscription($this->listener1);

        $repository->add($subscription);

        $this->assertTrue($this->uow->has($subscription));
    }

    public function testRepositoryOfNoSubscriptions()
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);

        $this->store
            ->expects($this->once())
            ->method('stream')
            ->with()
            ->willReturn($this->stream1)
        ;

        $this->stream1
            ->expects($this->once())
            ->method('of')
            ->with(SubscriptionStarted::class, SubscriptionCompleted::class)
            ->willReturnSelf()
        ;

        $this->isIteratorFor($this->stream1, []);

        $subscriptions = $repository->all();

        $this->assertTrue(is_iterable($subscriptions));
        $this->assertEquals([], iterator_to_array($subscriptions));
    }

    public function testRepositoryOfVariousSubscriptions()
    {
        $repository = new EventSourcedRepository($this->subscriptions, $this->listeners, $this->store, $this->uow);
        $subscription2 = new Event\Sourced\Subscription($this->listener2);
        $subscription3 = new Event\Sourced\Subscription($this->listener3);

        $this->store
            ->expects($this->exactly(3))
            ->method('stream')
            ->withConsecutive(
                [],
                [$this->id2],
                [$this->id3]
            )
            ->willReturnOnConsecutiveCalls(
                $this->stream1,
                $this->stream2,
                $this->stream3
            )
        ;

        $this->stream1
            ->expects($this->once())
            ->method('of')
            ->with(SubscriptionStarted::class, SubscriptionCompleted::class)
            ->willReturnSelf()
        ;

        $event1 = new SubscriptionStarted($this->event1, new \DateTime());
        $event2 = new SubscriptionStarted($this->event1, new \DateTime());
        $event3 = new SubscriptionCompleted();
        $event4 = new SubscriptionStarted($this->event1, new \DateTime());

        $this->isIteratorFor($this->stream1, [$event1, $event2, $event3, $event4]);

        $this->store
            ->expects($this->exactly(4))
            ->method('producerId')
            ->withConsecutive(
                [$event1],
                [$event2],
                [$event3],
                [$event4]
            )
            ->willReturnOnConsecutiveCalls(
                $this->id1,
                $this->id2,
                $this->id1,
                $this->id3
            )
        ;

        $this->listeners
            ->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                [$this->id2],
                [$this->id3]
            )
            ->willReturnOnConsecutiveCalls(
                $this->listener2,
                $this->listener3
            )
        ;

        $this->listener2
            ->expects($this->atLeastOnce())
            ->method('id')
            ->with()
            ->willReturn($this->id2)
        ;

        $this->listener3
            ->expects($this->atLeastOnce())
            ->method('id')
            ->with()
            ->willReturn($this->id3)
        ;

        $this->subscriptions
            ->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                [$this->listener2],
                [$this->listener3]
            )
            ->willReturnOnConsecutiveCalls(
                $subscription2,
                $subscription3
            )
        ;

        $this->stream2
            ->expects($this->once())
            ->method('empty')
            ->with()
            ->willReturn(false)
        ;

        $this->stream3
            ->expects($this->once())
            ->method('empty')
            ->with()
            ->willReturn(false)
        ;

        $subscriptions = $repository->all();

        $this->assertTrue(is_iterable($subscriptions));
        $this->assertEquals([$subscription2, $subscription3], iterator_to_array($subscriptions));
    }

    private function isIteratorFor(MockObject $iterator, array $items)
    {
        $internal = new \ArrayIterator($items);

        $iterator
            ->expects($this->any())
            ->method('rewind')
            ->willReturnCallback(function () use ($internal) {
                $internal->rewind();
            })
        ;

        $iterator
            ->expects($this->any())
            ->method('current')
            ->willReturnCallback(function () use ($internal) {
                return $internal->current();
            })
        ;

        $iterator
            ->expects($this->any())
            ->method('key')
            ->willReturnCallback(function () use ($internal) {
                return $internal->key();
            })
        ;

        $iterator
            ->expects($this->any())
            ->method('next')
            ->willReturnCallback(function () use ($internal) {
                $internal->next();
            })
        ;

        $iterator
            ->expects($this->any())
            ->method('valid')
            ->willReturnCallback(function () use ($internal) {
                return $internal->valid();
            })
        ;

        return $iterator;
    }
}
