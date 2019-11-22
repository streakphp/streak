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
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionCompleted;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionIgnoredEvent;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionListenedToEvent;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionRestarted;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionStarted;
use Streak\Domain\EventStore;
use Streak\Domain\Id\UUID;
use Streak\Infrastructure\Event\InMemoryStream;
use Streak\Infrastructure\Event\Sourced\Subscription;
use Streak\Infrastructure\FixedClock;
use Streak\Infrastructure\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Event\Subscription\CommittingSubscription
 */
class CommittingSubscriptionTest extends TestCase
{
    /**
     * @var Listener|MockObject
     */
    private $listener1;

    /**
     * @var Listener|Listener\Replayable|MockObject
     */
    private $listener2;

    /**
     * @var Listener|Listener\Completable|MockObject
     */
    private $listener3;

    /**
     * @var Listener|Listener\Resettable|MockObject
     */
    private $listener4;

    /**
     * @var Listener|Listener\Completable|Listener\Replayable|Listener\Resettable|MockObject
     */
    private $listener5;

    /**
     * @var Listener|Listener\Completable|Listener\Resettable|MockObject
     */
    private $listener6;

    /**
     * @var Listener|Listener\Resettable|Event\Picker|MockObject
     */
    private $listener7;

    /**
     * @var Listener|Event\Filterer|MockObject
     */
    private $listener8;

    /**
     * @var Listener\Id|MockObject
     */
    private $id1;

    /**
     * @var Listener\Id|MockObject
     */
    private $id2;

    /**
     * @var EventStore|MockObject
     */
    private $store;

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
     * @var Event|MockObject
     */
    private $event5;

    /**
     * @var FixedClock
     */
    private $clock;

    /**
     * @var UnitOfWork|MockObject
     */
    private $uow;

    public function setUp()
    {
        $this->listener1 = $this->getMockBuilder(Listener::class)->setMethods(['replay', 'reset', 'completed'])->getMockForAbstractClass();
        $this->listener2 = $this->getMockBuilder([Listener::class, Listener\Replayable::class])->getMock();
        $this->listener3 = $this->getMockBuilder([Listener::class, Listener\Completable::class])->getMock();
        $this->listener4 = $this->getMockBuilder([Listener::class, Listener\Resettable::class])->getMock();
        $this->listener5 = $this->getMockBuilder([Listener::class, Listener\Replayable::class, Listener\Resettable::class])->getMock();
        $this->listener6 = $this->getMockBuilder([Listener::class, Listener\Completable::class, Listener\Resettable::class])->getMock();
        $this->listener7 = $this->getMockBuilder([Listener::class, Listener\Resettable::class, Event\Picker::class])->getMock();
        $this->listener8 = $this->getMockBuilder([Listener::class, Event\Filterer::class])->getMock();

        $this->id1 = new class('f5e65690-e50d-4312-a175-b004ec1bd42a') extends UUID implements Listener\Id {
        };
        $this->id2 = new class('d01286b0-7dd6-4520-b714-0e9903ab39af') extends UUID implements Listener\Id {
        };

        $this->store = $this->getMockBuilder(EventStore::class)->getMockForAbstractClass();

        $this->stream1 = $this->getMockBuilder([Event\Stream::class, \IteratorAggregate::class])->getMock();
        $this->stream2 = $this->getMockBuilder([Event\Stream::class, \IteratorAggregate::class])->getMock();
        $this->stream3 = $this->getMockBuilder([Event\Stream::class, \IteratorAggregate::class])->getMock();

        $this->event1 = $this->getMockBuilder(Event::class)->setMockClassName('event1')->getMockForAbstractClass();
        $this->event1 = Event\Envelope::new($this->event1, UUID::random());
        $this->event2 = $this->getMockBuilder(Event::class)->setMockClassName('event2')->getMockForAbstractClass();
        $this->event2 = Event\Envelope::new($this->event2, UUID::random());
        $this->event3 = $this->getMockBuilder(Event::class)->setMockClassName('event3')->getMockForAbstractClass();
        $this->event3 = Event\Envelope::new($this->event3, UUID::random());
        $this->event4 = $this->getMockBuilder(Event::class)->setMockClassName('event4')->getMockForAbstractClass();
        $this->event4 = Event\Envelope::new($this->event4, UUID::random());
        $this->event5 = $this->getMockBuilder(Event::class)->setMockClassName('event5')->getMockForAbstractClass();
        $this->event5 = Event\Envelope::new($this->event5, UUID::random());

        $this->clock = new FixedClock(new \DateTime('2018-09-28 19:12:32.763188 +00:00'));

        $this->uow = $this->getMockBuilder(UnitOfWork::class)->getMockForAbstractClass();
    }

    public function testListener()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $this->listener1
            ->expects($this->never())
            ->method('replay')
        ;

        $decorated = new Subscription($this->listener1, $this->clock);
        $subscription = new CommittingSubscription($decorated, $this->uow);

        $this->assertSame($decorated, $subscription->subscription());

        $this->uow
            ->expects($this->exactly(9))
            ->method('commit')
        ;
        $this->uow
            ->expects($this->exactly(12))
            ->method('add')
            ->with($decorated)
        ;

        $this->assertSame($subscription->listener(), $this->listener1);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertEmpty($decorated->events());
        $this->assertSame(0, $decorated->version());
        $this->assertFalse($subscription->starting());
        $this->assertFalse($subscription->started());
        $this->assertFalse($subscription->completed());

        $this->uow
            ->expects($this->at(0))
            ->method('commit')
        ;
        $subscription->startFor($this->event1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertSame(0, $decorated->version());
        $this->assertEquals([new SubscriptionStarted($this->event1, $now)], $decorated->events());
        $this->assertTrue($subscription->starting());
        $this->assertTrue($subscription->started());
        $this->assertFalse($subscription->completed());

        $decorated->commit();

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertSame(1, $decorated->version());
        $this->assertEquals([], $decorated->events());
        $this->assertTrue($subscription->starting());
        $this->assertTrue($subscription->started());
        $this->assertFalse($subscription->completed());

        $this->stream1 = new InMemoryStream($this->event1, $this->event2);
        $this->stream2 = new InMemoryStream($this->event3, $this->event4);
        $this->stream3 = new InMemoryStream($this->event5);

        $this->store
            ->expects($this->exactly(3))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream1,
                $this->stream2,
                $this->stream3
            )
        ;

        $this->listener1
            ->expects($this->exactly(5))
            ->method('on')
            ->withConsecutive(
                [$this->event1],
                [$this->event2],
                [$this->event3],
                [$this->event4],
                [$this->event5]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false,
                false,
                true,
                true
            )
        ;
        $this->listener3
            ->expects($this->never())
            ->method('completed')
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1, $this->event2], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionIgnoredEvent($this->event2, $now)], $decorated->events());
        $this->assertSame(1, $decorated->version());
        $this->assertFalse($subscription->starting());
        $this->assertTrue($subscription->started());
        $this->assertFalse($subscription->completed());

        $decorated->commit();

        $this->assertEquals([], $decorated->events());
        $this->assertSame(3, $decorated->version());
        $this->assertFalse($subscription->starting());
        $this->assertTrue($subscription->started());
        $this->assertFalse($subscription->completed());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now)], $decorated->events());
        $this->assertSame(3, $decorated->version());
        $this->assertFalse($subscription->starting());
        $this->assertTrue($subscription->started());
        $this->assertFalse($subscription->completed());

        $decorated->commit();

        $this->assertEquals([], $decorated->events());
        $this->assertSame(5, $decorated->version());
        $this->assertFalse($subscription->starting());
        $this->assertTrue($subscription->started());
        $this->assertFalse($subscription->completed());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event5], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event5, $now)], $decorated->events());
        $this->assertSame(5, $decorated->version());
        $this->assertFalse($subscription->starting());
        $this->assertTrue($subscription->started());
        $this->assertFalse($subscription->completed());

        $decorated->commit();

        $this->assertEquals([], $decorated->events());
        $this->assertSame(6, $decorated->version());
        $this->assertFalse($subscription->starting());
        $this->assertTrue($subscription->started());
        $this->assertFalse($subscription->completed());
    }

    public function testListenerWithPicker()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener7
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $this->listener7
            ->expects($this->exactly(2))
            ->method('pick')
            ->willReturnOnConsecutiveCalls(
                $this->event2,
                $this->event1 // let's change starting point after restart
            )
        ;

        $decorated = new Subscription($this->listener7, $this->clock);
        $subscription = new CommittingSubscription($decorated, $this->uow);

        $this->uow
            ->expects($this->exactly(10))
            ->method('commit')
        ;
        $this->uow
            ->expects($this->exactly(12))
            ->method('add')
            ->with($decorated)
        ;

        $this->assertSame($subscription->listener(), $this->listener7);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertEmpty($decorated->events());
        $this->assertSame(0, $decorated->version());
        $this->assertFalse($subscription->starting());
        $this->assertFalse($subscription->started());
        $this->assertFalse($subscription->completed());

        $subscription->startFor($this->event3);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertSame(0, $decorated->version());
        $this->assertEquals([new SubscriptionStarted($this->event3, $now)], $decorated->events());
        $this->assertTrue($subscription->starting());
        $this->assertTrue($subscription->started());
        $this->assertFalse($subscription->completed());

        $decorated->commit();

        $this->stream1 = new InMemoryStream($this->event1, $this->event2, $this->event3);
        $this->stream2 = new InMemoryStream($this->event1, $this->event2, $this->event3, $this->event4);

        $this->store
            ->expects($this->exactly(2))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream1,
                $this->stream2
            )
        ;

        $this->listener7
            ->expects($this->exactly(6))
            ->method('on')
            ->withConsecutive(
                [$this->event2],
                [$this->event3],
                // second pass
                [$this->event1],
                [$this->event2],
                [$this->event3],
                [$this->event4]
            )
            ->willReturn(true)
        ;

        $events = $subscription->subscribeTo($this->store); // picker should pick $this->event2
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event2, $this->event3], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event2, $now), new SubscriptionListenedToEvent($this->event3, $now)], $decorated->events());
        $this->assertSame(1, $decorated->version());
        $this->assertFalse($subscription->starting());
        $this->assertTrue($subscription->started());
        $this->assertFalse($subscription->completed());

        $decorated->commit();

        $this->assertEquals([], $decorated->events());
        $this->assertSame(3, $decorated->version());

        $subscription->restart();

        $this->assertEquals([new SubscriptionRestarted($this->event3, $now)], $decorated->events());
        $this->assertSame(3, $decorated->version());
        $this->assertTrue($subscription->starting());
        $this->assertTrue($subscription->started());
        $this->assertFalse($subscription->completed());

        $decorated->commit();

        $this->assertEquals([], $decorated->events());
        $this->assertSame(4, $decorated->version());
        $this->assertTrue($subscription->starting());
        $this->assertTrue($subscription->started());
        $this->assertFalse($subscription->completed());

        $events = $subscription->subscribeTo($this->store); // after restart picker should pick $this->event1
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1, $this->event2, $this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionListenedToEvent($this->event2, $now), new SubscriptionListenedToEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now)], $decorated->events());
        $this->assertSame(4, $decorated->version());
        $this->assertFalse($subscription->starting());
        $this->assertTrue($subscription->started());
        $this->assertFalse($subscription->completed());

        $decorated->commit();

        $this->assertEquals([], $decorated->events());
        $this->assertSame(8, $decorated->version());
        $this->assertFalse($subscription->starting());
        $this->assertTrue($subscription->started());
        $this->assertFalse($subscription->completed());
    }

    public function testListenerWithFilterer()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->stream1 = new InMemoryStream($this->event1, $this->event2, $this->event3);
        $this->stream2 = new InMemoryStream($this->event1, $this->event2, $this->event3, $this->event4, $this->event5);

        $this->listener8
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->with()
            ->willReturn($this->id1)
        ;

        $decorated = new Subscription($this->listener8, $this->clock);
        $subscription = new CommittingSubscription($decorated, $this->uow);

        $this->uow
            ->expects($this->exactly(7))
            ->method('commit')
        ;
        $this->uow
            ->expects($this->exactly(9))
            ->method('add')
            ->with($decorated)
        ;

        $this->assertSame($subscription->listener(), $this->listener8);
        $this->assertNull($decorated->lastReplayed());
        $this->assertEmpty($decorated->events());
        $this->assertSame(0, $decorated->version());

        $subscription->startFor($this->event2);

        $this->assertNull($decorated->lastReplayed());
        $this->assertSame(0, $decorated->version());
        $this->assertEquals([new SubscriptionStarted($this->event2, $now)], $decorated->events());

        $decorated->commit();

        $this->assertNull($decorated->lastReplayed());
        $this->assertSame(1, $decorated->version());
        $this->assertEmpty($decorated->events());

        $this->store
            ->expects($this->exactly(2))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream1,
                $this->stream2
            )
        ;

        $this->listener8
            ->expects($this->exactly(2))
            ->method('filter')
            ->withConsecutive(
                [$this->equalTo((new InMemoryStream($this->event1, $this->event2, $this->event3))->without(SubscriptionStarted::class, SubscriptionListenedToEvent::class, SubscriptionIgnoredEvent::class, SubscriptionCompleted::class, SubscriptionRestarted::class))],
                [$this->equalTo((new InMemoryStream($this->event1, $this->event2, $this->event3, $this->event4, $this->event5))->without(SubscriptionStarted::class, SubscriptionListenedToEvent::class, SubscriptionIgnoredEvent::class, SubscriptionCompleted::class, SubscriptionRestarted::class))]
            )->willReturnOnConsecutiveCalls(
                $this->stream1,
                $this->stream2
            )
        ;
        $this->listener8
            ->expects($this->exactly(4))
            ->method('on')
            ->withConsecutive(
                [$this->event2],
                [$this->event3],
                [$this->event4],
                // second round
                [$this->event5]
            )
            ->willReturn(true)
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event2, $this->event3], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event2, $now), new SubscriptionListenedToEvent($this->event3, $now)], $decorated->events());
        $this->assertSame(1, $decorated->version());

        $decorated->commit();

        $this->assertEquals([], $decorated->events());
        $this->assertSame(3, $decorated->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event4, $this->event5], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event4, $now), new SubscriptionListenedToEvent($this->event5, $now)], $decorated->events());
        $this->assertSame(3, $decorated->version());

        $decorated->commit();

        $this->assertEquals([], $decorated->events());
        $this->assertSame(5, $decorated->version());
    }

    public function testTransactionalListenerWithoutReplaying()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener3
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $this->listener1
            ->expects($this->never())
            ->method('replay')
        ;

        $decorated = new Subscription($this->listener3, $this->clock);
        $subscription = new CommittingSubscription($decorated, $this->uow);

        $this->uow
            ->expects($this->exactly(9))
            ->method('commit')
        ;
        $this->uow
            ->expects($this->exactly(12))
            ->method('add')
            ->with($decorated)
        ;

        $this->assertSame($subscription->listener(), $this->listener3);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertEmpty($decorated->events());
        $this->assertSame(0, $decorated->version());

        $subscription->startFor($this->event1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertSame(0, $decorated->version());
        $this->assertEquals([new SubscriptionStarted($this->event1, $now)], $decorated->events());

        $decorated->commit();

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertSame(1, $decorated->version());
        $this->assertEquals([], $decorated->events());

        $this->stream1 = new InMemoryStream($this->event1, $this->event2);
        $this->stream2 = new InMemoryStream($this->event3, $this->event4);
        $this->stream3 = new InMemoryStream($this->event5);

        $this->store
            ->expects($this->exactly(3))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream1,
                $this->stream2,
                $this->stream3
            )
        ;

        $this->listener3
            ->expects($this->exactly(5))
            ->method('on')
            ->withConsecutive(
                [$this->event1],
                [$this->event2],
                [$this->event3],
                [$this->event4],
                [$this->event5]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false,
                false,
                true,
                true
            )
        ;
        $this->listener3
            ->expects($this->exactly(5))
            ->method('completed')
            ->willReturnOnConsecutiveCalls(
                false,
                false,
                false,
                false,
                true
            )
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1, $this->event2], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionIgnoredEvent($this->event2, $now)], $decorated->events());
        $this->assertSame(1, $decorated->version());

        $decorated->commit();

        $this->assertEquals([], $decorated->events());
        $this->assertSame(3, $decorated->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now)], $decorated->events());
        $this->assertSame(3, $decorated->version());

        $decorated->commit();

        $this->assertEquals([], $decorated->events());
        $this->assertSame(5, $decorated->version());

        $events = $subscription->subscribeTo($this->store, PHP_INT_MAX); // subscription will stop listening to #events right after it being completed, even if $limit was not exhausted.
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event5], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event5, $now), new SubscriptionCompleted($now)], $decorated->events());
        $this->assertSame(5, $decorated->version());

        $decorated->commit();

        $this->assertEquals([], $decorated->events());
        $this->assertSame(7, $decorated->version());
    }

    public function testNonReplayableListenerWithReplaying()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $this->listener1
            ->expects($this->never())
            ->method('replay')
        ;

        $decorated = new Subscription($this->listener1, $this->clock);
        $subscription = new CommittingSubscription($decorated, $this->uow);

        $this->uow
            ->expects($this->exactly(5))
            ->method('commit')
        ;
        $this->uow
            ->expects($this->exactly(7))
            ->method('add')
            ->with($decorated)
        ;

        $this->assertSame($subscription->listener(), $this->listener1);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertEmpty($decorated->events());
        $this->assertSame(0, $decorated->version());

        $event0 = new SubscriptionStarted($this->event1, $now);
        $event0 = Event\Envelope::new($event0, $this->id1, 1);
        $event1 = new SubscriptionListenedToEvent($this->event1, $now);
        $event1 = Event\Envelope::new($event1, $this->id1, 2);
        $event2 = new SubscriptionListenedToEvent($this->event2, $now);
        $event2 = Event\Envelope::new($event2, $this->id1, 3);

        $this->stream1 = new InMemoryStream($event0, $event1, $event2);

        $decorated->replay($this->stream1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertSame($event2, $decorated->lastReplayed());
        $this->assertSame(3, $decorated->version());
        $this->assertEmpty($decorated->events());

        $this->stream2 = new InMemoryStream($this->event3, $this->event4);
        $this->stream3 = new InMemoryStream($this->event5);

        $this->store
            ->expects($this->exactly(2))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream2,
                $this->stream3
            )
        ;

        $this->listener1
            ->expects($this->exactly(3))
            ->method('on')
            ->withConsecutive(
                [$this->event3],
                [$this->event4],
                [$this->event5]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true,
                true
            )
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now)], $decorated->events());
        $this->assertSame(3, $decorated->version());

        $decorated->commit();

        $this->assertEquals([], $decorated->events());
        $this->assertSame(5, $decorated->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event5], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event5, $now)], $decorated->events());
        $this->assertSame(5, $decorated->version());

        $decorated->commit();

        $this->assertEquals([], $decorated->events());
        $this->assertSame(6, $decorated->version());
    }

    public function testReplayableListenerWithReplaying()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener2
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->with()
            ->willReturn($this->id1)
        ;

        $decorated = new Subscription($this->listener2, $this->clock);
        $subscription = new CommittingSubscription($decorated, $this->uow);

        $this->uow
            ->expects($this->exactly(5))
            ->method('commit')
        ;
        $this->uow
            ->expects($this->exactly(7))
            ->method('add')
            ->with($decorated)
        ;

        $this->assertSame($subscription->listener(), $this->listener2);
        $this->assertNull($decorated->lastReplayed());
        $this->assertEmpty($decorated->events());
        $this->assertSame(0, $decorated->version());

        $event0 = new SubscriptionStarted($this->event1, $now);
        $event0 = Event\Envelope::new($event0, $this->id1, 1);
        $event1 = new SubscriptionListenedToEvent($this->event1, $now);
        $event1 = Event\Envelope::new($event1, $this->id1, 2);
        $event2 = new SubscriptionListenedToEvent($this->event2, $now);
        $event2 = Event\Envelope::new($event2, $this->id1, 3);

        $this->stream1 = new InMemoryStream($event0, $event1, $event2);
        $this->listener2
            ->expects($this->once())
            ->method('replay')
            ->with($this->callback(function ($stream) {
                $stream = iterator_to_array($stream);

                return $this->equalTo([$this->event1, $this->event2])->evaluate($stream);
            }));

        $decorated->replay($this->stream1);

        $this->assertSame($event2, $decorated->lastReplayed());
        $this->assertSame(3, $decorated->version());
        $this->assertEmpty($decorated->events());

        $this->stream2 = new InMemoryStream($this->event3, $this->event4);
        $this->stream3 = new InMemoryStream($this->event5);

        $this->store
            ->expects($this->exactly(2))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream2,
                $this->stream3
            )
        ;

        $this->listener2
            ->expects($this->exactly(3))
            ->method('on')
            ->withConsecutive(
                [$this->event3],
                [$this->event4],
                [$this->event5]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true,
                true
            )
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now)], $decorated->events());
        $this->assertSame(3, $decorated->version());

        $decorated->commit();

        $this->assertEquals([], $decorated->events());
        $this->assertSame(5, $decorated->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event5], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event5, $now)], $decorated->events());
        $this->assertSame(5, $decorated->version());

        $decorated->commit();

        $this->assertEquals([], $decorated->events());
        $this->assertSame(6, $decorated->version());
    }

    public function testReplayableListener()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener2
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->with()
            ->willReturn($this->id1)
        ;

        $decorated = new Subscription($this->listener2, $this->clock);
        $subscription = new CommittingSubscription($decorated, $this->uow);

        $this->uow
            ->expects($this->never())
            ->method('commit')
        ;
        $this->uow
            ->expects($this->never())
            ->method('add')
        ;

        $this->assertSame($subscription->listener(), $this->listener2);
        $this->assertFalse($subscription->completed());
        $this->assertNull($decorated->lastReplayed());
        $this->assertEmpty($decorated->events());
        $this->assertSame(0, $decorated->version());

        $event0 = new SubscriptionStarted($this->event1, $now);
        $event0 = Event\Envelope::new($event0, $this->id1, 1);
        $event1 = new SubscriptionListenedToEvent($this->event1, $now);
        $event1 = Event\Envelope::new($event1, $this->id1, 2);
        $event2 = new SubscriptionListenedToEvent($this->event2, $now);
        $event2 = Event\Envelope::new($event2, $this->id1, 3);

        $this->stream1 = new InMemoryStream($event0, $event1, $event2);

        $this->listener2
            ->expects($this->once())
            ->method('replay')
            ->with($this->callback(function ($stream) {
                $stream = iterator_to_array($stream);

                return $this->equalTo([$this->event1, $this->event2])->evaluate($stream);
            }));

        $decorated->replay($this->stream1);

        $this->assertSame($event2, $decorated->lastReplayed());
        $this->assertEmpty($decorated->events());
    }

    public function testCompletingListener()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener3
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->with()
            ->willReturn($this->id1)
        ;

        $decorated = new Subscription($this->listener3, $this->clock);
        $subscription = new CommittingSubscription($decorated, $this->uow);

        $this->uow
            ->expects($this->exactly(3))
            ->method('commit')
        ;
        $this->uow
            ->expects($this->exactly(4))
            ->method('add')
            ->with($decorated)
        ;

        $this->assertSame($subscription->listener(), $this->listener3);
        $this->assertFalse($subscription->completed());
        $this->assertNull($decorated->lastReplayed());
        $this->assertEmpty($decorated->events());
        $this->assertSame(0, $decorated->version());

        $event0 = new SubscriptionStarted($this->event1, $now);
        $event0 = Event\Envelope::new($event0, $this->id1, 1);

        $this->stream1 = new InMemoryStream($event0);

        $decorated->replay($this->stream1);

        $this->assertSame($event0, $decorated->lastReplayed());
        $this->assertEmpty($decorated->events());
        $this->assertSame(1, $decorated->version());
        $this->assertFalse($subscription->completed());

        // mind you that $this->event3 won't be listened to, because $this->event2 completes subscription
        $this->stream2 = new InMemoryStream($this->event1, $this->event2, $this->event3);

        $this->store
            ->expects($this->once())
            ->method('stream')
            ->willReturn($this->stream2)
        ;

        $this->listener3
            ->expects($this->exactly(2))
            ->method('on')
            ->withConsecutive(
                [$this->event1],
                [$this->event2]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                true
            )
        ;

        $this->listener3
            ->expects($this->exactly(2))
            ->method('completed')
            ->willReturnOnConsecutiveCalls(
                false,
                true
            )
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1, $this->event2], $events);
        $this->assertTrue($subscription->completed());
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionListenedToEvent($this->event2, $now), new SubscriptionCompleted($now)], $decorated->events());
        $this->assertSame($event0, $decorated->lastReplayed());
        $this->assertSame(1, $decorated->version());

        $decorated->commit();

        $this->assertTrue($subscription->completed());
        $this->assertEmpty($decorated->events());
        $this->assertSame($event0, $decorated->lastReplayed());
        $this->assertSame(4, $decorated->version());
    }

    public function testStartingAlreadyStartedSubscription()
    {
        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;

        $decorated = new Subscription($this->listener1, $this->clock);
        $subscription = new CommittingSubscription($decorated, $this->uow);

        $this->uow
            ->expects($this->exactly(1))
            ->method('commit')
        ;
        $this->uow
            ->expects($this->exactly(2))
            ->method('add')
            ->with($decorated)
        ;

        $this->assertSame($subscription->listener(), $this->listener1);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertEmpty($decorated->events());

        $subscription->startFor($this->event1);

        $this->expectExceptionObject(new Event\Subscription\Exception\SubscriptionAlreadyStarted($subscription));

        $subscription->startFor($this->event2);
    }

    public function testSubscribingAlreadyCompletedListener()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener3
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->with()
            ->willReturn($this->id1)
        ;

        $decorated = new Subscription($this->listener3, $this->clock);
        $subscription = new CommittingSubscription($decorated, $this->uow);

        $this->uow
            ->expects($this->never())
            ->method('commit')
        ;
        $this->uow
            ->expects($this->exactly(1))
            ->method('add')
            ->with($decorated)
        ;

        $this->assertSame($subscription->listener(), $this->listener3);
        $this->assertFalse($subscription->completed());

        $event0 = new SubscriptionStarted($this->event1, $now);
        $event0 = Event\Envelope::new($event0, $this->id1, 1);
        $event1 = new SubscriptionListenedToEvent($this->event2, $now);
        $event1 = Event\Envelope::new($event1, $this->id1, 2);
        $event2 = new SubscriptionCompleted($now);
        $event2 = Event\Envelope::new($event2, $this->id1, 3);

        $this->stream1 = new InMemoryStream($event0, $event1, $event2);

        $decorated->replay($this->stream1);

        $this->assertSame($event2, $decorated->lastReplayed());
        $this->assertEmpty($decorated->events());
        $this->assertSame(3, $decorated->version());
        $this->assertTrue($subscription->completed());

        $this->store
            ->expects($this->never())
            ->method('stream')
        ;

        $this->expectExceptionObject(new Event\Subscription\Exception\SubscriptionAlreadyCompleted($subscription));

        $events = $subscription->subscribeTo($this->store);

        iterator_to_array($events);
    }

    public function testSubscribingRestartedAndCompletedListener()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener3
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->with()
            ->willReturn($this->id1)
        ;

        $decorated = new Subscription($this->listener3, $this->clock);
        $subscription = new CommittingSubscription($decorated, $this->uow);

        $this->uow
            ->expects($this->never())
            ->method('commit')
        ;
        $this->uow
            ->expects($this->exactly(1))
            ->method('add')
            ->with($decorated)
        ;

        $this->assertSame($subscription->listener(), $this->listener3);
        $this->assertFalse($subscription->completed());

        $event0 = new SubscriptionStarted($this->event1, $now);
        $event0 = Event\Envelope::new($event0, $this->id1, 1);
        $event1 = new SubscriptionListenedToEvent($this->event2, $now);
        $event1 = Event\Envelope::new($event1, $this->id1, 2);
        $event2 = new SubscriptionCompleted($now);
        $event2 = Event\Envelope::new($event2, $this->id1, 3);
        $event3 = new SubscriptionRestarted($this->event1, $now);
        $event3 = Event\Envelope::new($event3, $this->id1, 4);
        $event4 = new SubscriptionListenedToEvent($this->event2, $now);
        $event4 = Event\Envelope::new($event4, $this->id1, 5);
        $event5 = new SubscriptionCompleted($now);
        $event5 = Event\Envelope::new($event5, $this->id1, 6);

        $this->stream1 = new InMemoryStream($event0, $event1, $event2, $event3, $event4, $event5);

        $decorated->replay($this->stream1);

        $this->assertSame($event5, $decorated->lastReplayed());
        $this->assertEmpty($decorated->events());
        $this->assertSame(6, $decorated->version());
        $this->assertTrue($subscription->completed());

        $this->store
            ->expects($this->never())
            ->method('stream')
        ;

        $this->expectExceptionObject(new Event\Subscription\Exception\SubscriptionAlreadyCompleted($subscription));

        $events = $subscription->subscribeTo($this->store);

        iterator_to_array($events);
    }

    public function testNotStartedListener()
    {
        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;

        $decorated = new Subscription($this->listener1, $this->clock);
        $subscription = new CommittingSubscription($decorated, $this->uow);

        $this->uow
            ->expects($this->never())
            ->method('commit')
        ;
        $this->uow
            ->expects($this->exactly(1))
            ->method('add')
            ->with($decorated)
        ;

        $this->assertSame($subscription->listener(), $this->listener1);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertEmpty($decorated->events());

        $this->store
            ->expects($this->never())
            ->method('stream')
        ;

        $this->listener1
            ->expects($this->never())
            ->method('on')
        ;

        $this->expectExceptionObject(new Event\Subscription\Exception\SubscriptionNotStartedYet($subscription));

        $events = $subscription->subscribeTo($this->store);

        iterator_to_array($events);
    }

    public function testRestartingSubscriptionForResettableButNonReplayableListener()
    {
        $this->listener4
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->with()
            ->willReturn($this->id1)
        ;

        $decorated = new Subscription($this->listener4, $this->clock);
        $subscription = new CommittingSubscription($decorated, $this->uow);

        $this->uow
            ->expects($this->exactly(7))
            ->method('commit')
        ;
        $this->uow
            ->expects($this->exactly(8))
            ->method('add')
            ->with($decorated)
        ;

        $this->assertSame($subscription->listener(), $this->listener4);
        $this->assertNull($decorated->lastReplayed());
        $this->assertEmpty($decorated->events());
        $this->assertSame(0, $decorated->version());

        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');
        $event0 = new SubscriptionStarted($this->event1, $now);
        $event0 = Event\Envelope::new($event0, $this->id1, 1);
        $event1 = new SubscriptionListenedToEvent($this->event1, $now);
        $event1 = Event\Envelope::new($event1, $this->id1, 2);
        $event2 = new SubscriptionListenedToEvent($this->event2, $now);
        $event2 = Event\Envelope::new($event2, $this->id1, 3);
        $event3 = new SubscriptionIgnoredEvent($this->event3, $now);
        $event3 = Event\Envelope::new($event3, $this->id1, 4);

        $this->stream1 = new InMemoryStream($event0, $event1, $event2, $event3);

        $decorated->replay($this->stream1);

        $this->assertSame($event3, $decorated->lastReplayed());
        $this->assertSame([], $decorated->events());
        $this->assertSame(4, $decorated->version());

        $subscription->restart();

        $this->assertSame($event3, $decorated->lastReplayed());
        $this->assertEquals([new SubscriptionRestarted($this->event1, $now)], $decorated->events());
        $this->assertSame(4, $decorated->version());

        $decorated->commit();

        $this->assertSame($event3, $decorated->lastReplayed());
        $this->assertSame([], $decorated->events());
        $this->assertSame(5, $decorated->version());

        $subscription->restart();

        // nothing changed as consecutive restarts are ignored
        $this->assertSame($event3, $decorated->lastReplayed());
        $this->assertSame([], $decorated->events());
        $this->assertSame(5, $decorated->version());

        $this->stream2 = new InMemoryStream($this->event1, $this->event3, $this->event4, $this->event5); // lets say that after restart listener do not need to listen to $this->>event2

        $this->store
            ->expects($this->exactly(1))
            ->method('stream')
            ->willReturn($this->stream2)
        ;

        $this->listener4
            ->expects($this->once())
            ->method('reset')
        ;
        $this->listener4
            ->expects($this->exactly(4))
            ->method('on')
            ->withConsecutive(
                [$this->event1],
                [$this->event3],
                [$this->event4],
                [$this->event5]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                true,
                false,
                true
            )
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertSame([$this->event1, $this->event3, $this->event4, $this->event5], $events);
        $this->assertSame($event3, $decorated->lastReplayed());
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionListenedToEvent($this->event3, $now), new SubscriptionIgnoredEvent($this->event4, $now), new SubscriptionListenedToEvent($this->event5, $now)], $decorated->events());
        $this->assertSame(5, $decorated->version());

        $decorated->commit();

        $this->assertSame($event3, $decorated->lastReplayed());
        $this->assertSame([], $decorated->events());
        $this->assertSame(9, $decorated->version());
    }

    public function testRestartingSubscriptionForReplayableListener()
    {
        $this->listener5
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->with()
            ->willReturn($this->id1)
        ;

        $decorated = new Subscription($this->listener5, $this->clock);
        $subscription = new CommittingSubscription($decorated, $this->uow);

        $this->uow
            ->expects($this->exactly(7))
            ->method('commit')
        ;
        $this->uow
            ->expects($this->exactly(8))
            ->method('add')
            ->with($decorated)
        ;

        $this->assertSame($subscription->listener(), $this->listener5);
        $this->assertNull($decorated->lastReplayed());
        $this->assertEmpty($decorated->events());
        $this->assertSame(0, $decorated->version());

        $this->listener5
            ->expects($this->exactly(4))
            ->method('on')
            ->withConsecutive(
                [$this->event2],
                [$this->event3],
                [$this->event4],
                [$this->event5]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                true,
                false,
                true
            )
        ;

        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');
        $event0 = new SubscriptionStarted($this->event2, $now);
        $event0 = Event\Envelope::new($event0, $this->id1, 1);
        $event1 = new SubscriptionListenedToEvent($this->event2, $now);
        $event1 = Event\Envelope::new($event1, $this->id1, 2);
        $event2 = new SubscriptionListenedToEvent($this->event3, $now);
        $event2 = Event\Envelope::new($event2, $this->id1, 3);

        $this->stream1 = new InMemoryStream($event0, $event1, $event2);

        $decorated->replay($this->stream1);

        $this->assertSame($event2, $decorated->lastReplayed());
        $this->assertSame([], $decorated->events());
        $this->assertSame(3, $decorated->version());

        $subscription->restart();

        $this->assertSame($event2, $decorated->lastReplayed());
        $this->assertEquals([new SubscriptionRestarted($this->event2, $now)], $decorated->events());
        $this->assertSame(3, $decorated->version());

        $decorated->commit();

        $this->assertSame($event2, $decorated->lastReplayed());
        $this->assertSame([], $decorated->events());
        $this->assertSame(4, $decorated->version());

        $subscription->restart();

        // nothing changed as consecutive restarts are ignored
        $this->assertSame($event2, $decorated->lastReplayed());
        $this->assertSame([], $decorated->events());
        $this->assertSame(4, $decorated->version());

        $this->stream3 = new InMemoryStream($this->event2, $this->event3, $this->event4, $this->event5);

        $this->store
            ->expects($this->exactly(1))
            ->method('stream')
            ->willReturnReference($this->stream3)
        ;

        $this->listener5
            ->expects($this->once())
            ->method('reset')
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertSame([$this->event2, $this->event3, $this->event4, $this->event5], $events);
        $this->assertSame($event2, $decorated->lastReplayed());
        $this->assertEquals([new SubscriptionListenedToEvent($this->event2, $now), new SubscriptionListenedToEvent($this->event3, $now), new SubscriptionIgnoredEvent($this->event4, $now), new SubscriptionListenedToEvent($this->event5, $now)], $decorated->events());
        $this->assertSame(4, $decorated->version());

        $decorated->commit();

        $this->assertSame($event2, $decorated->lastReplayed());
        $this->assertSame([], $decorated->events());
        $this->assertSame(8, $decorated->version());
    }

    public function testRestartingNotStartedSubscription()
    {
        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;

        $decorated = new Subscription($this->listener1, $this->clock);
        $subscription = new CommittingSubscription($decorated, $this->uow);

        $this->uow
            ->expects($this->never())
            ->method('commit')
        ;
        $this->uow
            ->expects($this->exactly(1))
            ->method('add')
            ->with($decorated)
        ;

        $this->assertSame($subscription->listener(), $this->listener1);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertEmpty($decorated->events());

        $this->expectExceptionObject(new Event\Subscription\Exception\SubscriptionNotStartedYet($subscription));

        $subscription->restart();
    }

    public function testRestartingCompletedSubscription()
    {
        $this->listener6
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->with()
            ->willReturn($this->id1)
        ;

        $decorated = new Subscription($this->listener6, $this->clock);
        $subscription = new CommittingSubscription($decorated, $this->uow);

        $this->uow
            ->expects($this->exactly(6))
            ->method('commit')
        ;
        $this->uow
            ->expects($this->exactly(7))
            ->method('add')
            ->with($decorated)
        ;

        $this->assertSame($subscription->listener(), $this->listener6);
        $this->assertNull($decorated->lastReplayed());
        $this->assertEmpty($decorated->events());
        $this->assertSame(0, $decorated->version());
        $this->assertFalse($subscription->completed());

        $this->listener6
            ->expects($this->exactly(4))
            ->method('on')
            ->withConsecutive(
                [$this->event2],
                [$this->event3],
                [$this->event4],
                [$this->event5]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                true,
                true,
                true
            )
        ;

        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');
        $event0 = new SubscriptionStarted($this->event2, $now);
        $event0 = Event\Envelope::new($event0, $this->id1, 1);
        $event1 = new SubscriptionListenedToEvent($this->event2, $now);
        $event1 = Event\Envelope::new($event1, $this->id1, 2);
        $event2 = new SubscriptionListenedToEvent($this->event3, $now);
        $event2 = Event\Envelope::new($event2, $this->id1, 3);
        $event3 = new SubscriptionCompleted($now);
        $event3 = Event\Envelope::new($event3, $this->id1, 4);

        $this->stream1 = new InMemoryStream($event0, $event1, $event2, $event3);

        $decorated->replay($this->stream1);

        $this->assertSame($event3, $decorated->lastReplayed());
        $this->assertSame([], $decorated->events());
        $this->assertSame(4, $decorated->version());
        $this->assertTrue($subscription->completed());

        $subscription->restart();

        $this->assertSame($event3, $decorated->lastReplayed());
        $this->assertEquals([new SubscriptionRestarted($this->event2, $now)], $decorated->events());
        $this->assertSame(4, $decorated->version());
        $this->assertFalse($subscription->completed());

        $decorated->commit();

        $this->assertSame($event3, $decorated->lastReplayed());
        $this->assertSame([], $decorated->events());
        $this->assertSame(5, $decorated->version());
        $this->assertFalse($subscription->completed());

        $this->stream3 = new InMemoryStream($this->event2, $this->event3, $this->event4, $this->event5);

        $this->store
            ->expects($this->exactly(1))
            ->method('stream')
            ->willReturnReference($this->stream3)
        ;

        $this->listener6
            ->expects($this->once())
            ->method('reset')
        ;

        $this->listener6
            ->expects($this->exactly(4))
            ->method('on')
            ->withConsecutive(
                [$this->event2],
                [$this->event3],
                [$this->event4],
                [$this->event5]
            )
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertSame([$this->event2, $this->event3, $this->event4, $this->event5], $events);
        $this->assertSame($event3, $decorated->lastReplayed());
        $this->assertEquals([new SubscriptionListenedToEvent($this->event2, $now), new SubscriptionListenedToEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now), new SubscriptionListenedToEvent($this->event5, $now)], $decorated->events());
        $this->assertSame(5, $decorated->version());
        $this->assertFalse($subscription->completed());

        $decorated->commit();

        $this->assertSame($event3, $decorated->lastReplayed());
        $this->assertSame([], $decorated->events());
        $this->assertSame(9, $decorated->version());
        $this->assertFalse($subscription->completed());
    }

    public function testRestartingNonResettableListener()
    {
        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->with()
            ->willReturn($this->id1)
        ;

        $decorated = new Subscription($this->listener1, $this->clock);
        $subscription = new CommittingSubscription($decorated, $this->uow);

        $this->uow
            ->expects($this->never())
            ->method('commit')
        ;
        $this->uow
            ->expects($this->exactly(1))
            ->method('add')
            ->with($decorated)
        ;

        $this->assertSame($subscription->listener(), $this->listener1);
        $this->assertFalse($subscription->completed());
        $this->assertNull($decorated->lastReplayed());
        $this->assertEmpty($decorated->events());
        $this->assertSame(0, $decorated->version());
        $this->assertFalse($subscription->completed());

        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');
        $event0 = new SubscriptionStarted($this->event1, $now);
        $event0 = Event\Envelope::new($event0, $this->id1, 1);
        $event1 = new SubscriptionListenedToEvent($this->event2, $now);
        $event1 = Event\Envelope::new($event1, $this->id1, 2);
        $event2 = new SubscriptionListenedToEvent($this->event3, $now);
        $event2 = Event\Envelope::new($event2, $this->id1, 3);

        $this->stream1 = new InMemoryStream($event0, $event1, $event2);

        $decorated->replay($this->stream1);

        $this->assertSame($event2, $decorated->lastReplayed());
        $this->assertEmpty($decorated->events());
        $this->assertSame(3, $decorated->version());
        $this->assertFalse($subscription->completed());

        $this->listener1
            ->expects($this->never())
            ->method('reset')
        ;

        $this->expectExceptionObject(new Event\Subscription\Exception\SubscriptionRestartNotPossible($subscription));

        $subscription->restart();
    }

    public function testStartingSubscriptionWithResettableListenerWithFirstEventIgnored()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener4
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $decorated = new Subscription($this->listener4, $this->clock);
        $subscription = new CommittingSubscription($decorated, $this->uow);

        $this->uow
            ->expects($this->exactly(3))
            ->method('commit')
        ;
        $this->uow
            ->expects($this->exactly(4))
            ->method('add')
            ->with($decorated)
        ;

        $this->assertSame($subscription->listener(), $this->listener4);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertEmpty($decorated->events());
        $this->assertSame(0, $decorated->version());

        $subscription->startFor($this->event1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertSame(0, $decorated->version());
        $this->assertEquals([new SubscriptionStarted($this->event1, $now)], $decorated->events());

        $decorated->commit();

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertSame(1, $decorated->version());
        $this->assertEquals([], $decorated->events());

        $this->stream1 = new InMemoryStream($this->event1);

        $this->store
            ->expects($this->exactly(1))
            ->method('stream')
            ->willReturn($this->stream1)
        ;

        $this->listener4
            ->expects($this->exactly(1))
            ->method('on')
            ->with($this->event1)
            ->willReturn(false)
        ;

        $this->listener4
            ->expects($this->once())
            ->method('reset')
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1], $events);
        $this->assertEquals([new SubscriptionIgnoredEvent($this->event1, $now)], $decorated->events());
        $this->assertSame(1, $decorated->version());

        $decorated->commit();

        $this->assertEmpty($decorated->events());
        $this->assertSame(2, $decorated->version());
    }

    public function testStartingSubscriptionWithResettableListenerWithFirstEventNotIgnored()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener4
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $decorated = new Subscription($this->listener4, $this->clock);
        $subscription = new CommittingSubscription($decorated, $this->uow);

        $this->uow
            ->expects($this->exactly(3))
            ->method('commit')
        ;
        $this->uow
            ->expects($this->exactly(4))
            ->method('add')
            ->with($decorated)
        ;

        $this->assertSame($subscription->listener(), $this->listener4);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertEmpty($decorated->events());
        $this->assertSame(0, $decorated->version());

        $subscription->startFor($this->event1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertSame(0, $decorated->version());
        $this->assertEquals([new SubscriptionStarted($this->event1, $now)], $decorated->events());

        $decorated->commit();

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertSame(1, $decorated->version());
        $this->assertEquals([], $decorated->events());

        $this->stream1 = new InMemoryStream($this->event1);

        $this->store
            ->expects($this->exactly(1))
            ->method('stream')
            ->willReturn($this->stream1)
        ;

        $this->listener4
            ->expects($this->exactly(1))
            ->method('on')
            ->with($this->event1)
            ->willReturn(true)
        ;

        $this->listener4
            ->expects($this->once())
            ->method('reset')
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, $now)], $decorated->events());
        $this->assertSame(1, $decorated->version());

        $decorated->commit();

        $this->assertEmpty($decorated->events());
        $this->assertSame(2, $decorated->version());
    }

    public function testRestartingFreshlyStartedSubscription()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener4
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $decorated = new Subscription($this->listener4, $this->clock);
        $subscription = new CommittingSubscription($decorated, $this->uow);

        $this->uow
            ->expects($this->exactly(2))
            ->method('commit')
        ;
        $this->uow
            ->expects($this->exactly(2))
            ->method('add')
            ->with($decorated)
        ;

        $this->assertSame($subscription->listener(), $this->listener4);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertEmpty($decorated->events());
        $this->assertSame(0, $decorated->version());

        $subscription->startFor($this->event1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertSame(0, $decorated->version());
        $this->assertEquals([new SubscriptionStarted($this->event1, $now)], $decorated->events());

        $decorated->commit();

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertSame(1, $decorated->version());
        $this->assertEquals([], $decorated->events());

        $subscription->restart();

        // nothing changed as consecutive restarts are ignored
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertSame(1, $decorated->version());
        $this->assertEquals([], $decorated->events());
    }

    public function testNonPositiveLimitGivenWhileSubscribingToTheEventStoreBeforeStarting()
    {
        $decorated = new Subscription($this->listener1, $this->clock);
        $subscription = new CommittingSubscription($decorated, $this->uow);

        $this->uow
            ->expects($this->never())
            ->method('commit')
        ;
        $this->uow
            ->expects($this->once())
            ->method('add')
            ->with($decorated)
        ;

        $this->store
            ->expects($this->never())
            ->method('stream')
        ;

        $this->listener1
            ->expects($this->never())
            ->method('on')
        ;
        $this->listener3
            ->expects($this->never())
            ->method('completed')
        ;

        $this->expectExceptionObject(new \InvalidArgumentException('$limit must be a positive integer, but 0 was given.'));

        $events = $subscription->subscribeTo($this->store, 0);
        $events->rewind();
    }

    public function testNonPositiveLimitGivenWhileSubscribingToTheEventStoreAfterStarting()
    {
        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->with()
            ->willReturn($this->id1)
        ;

        $decorated = new Subscription($this->listener1, $this->clock);
        $subscription = new CommittingSubscription($decorated, $this->uow);

        $this->uow
            ->expects($this->once())
            ->method('commit')
        ;
        $this->uow
            ->expects($this->exactly(2))
            ->method('add')
            ->with($decorated)
        ;

        $subscription->startFor($this->event1);
        $decorated->commit();

        $this->store
            ->expects($this->never())
            ->method('stream')
        ;

        $this->listener1
            ->expects($this->never())
            ->method('on')
        ;
        $this->listener3
            ->expects($this->never())
            ->method('completed')
        ;

        $this->expectExceptionObject(new \InvalidArgumentException('$limit must be a positive integer, but -1 was given.'));

        $events = $subscription->subscribeTo($this->store, -1);
        $events->rewind();
    }

    public function testContinuousListeningWithNumberOfEventsBeingExactlyImposedLimit()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $this->listener1
            ->expects($this->never())
            ->method('replay')
        ;

        $decorated = new Subscription($this->listener1, $this->clock);
        $subscription = new CommittingSubscription($decorated, $this->uow);

        $this->uow
            ->expects($this->exactly(7))
            ->method('commit')
        ;
        $this->uow
            ->expects($this->exactly(8))
            ->method('add')
            ->with($decorated)
        ;

        $this->assertSame($subscription->listener(), $this->listener1);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertEmpty($decorated->events());
        $this->assertSame(0, $decorated->version());

        $subscription->startFor($this->event1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertSame(0, $decorated->version());
        $this->assertEquals([new SubscriptionStarted($this->event1, $now)], $decorated->events());

        $decorated->commit();

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertSame(1, $decorated->version());
        $this->assertEquals([], $decorated->events());

        $this->stream1 = new InMemoryStream($this->event1, $this->event2);
        $this->stream2 = new InMemoryStream($this->event3, $this->event4);
        $this->stream3 = new InMemoryStream($this->event5);

        $this->store
            ->expects($this->exactly(3))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream1,
                $this->stream2,
                $this->stream3
            )
        ;

        $this->listener1
            ->expects($this->exactly(5))
            ->method('on')
            ->withConsecutive(
                [$this->event1],
                [$this->event2],
                [$this->event3],
                [$this->event4],
                [$this->event5]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false,
                false,
                true,
                true
            )
        ;
        $this->listener3
            ->expects($this->never())
            ->method('completed')
        ;

        $events = $subscription->subscribeTo($this->store, 5);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1, $this->event2, $this->event3, $this->event4, $this->event5], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionIgnoredEvent($this->event2, $now), new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now), new SubscriptionListenedToEvent($this->event5, $now)], $decorated->events());
        $this->assertSame(1, $decorated->version());

        $decorated->commit();

        $this->assertEquals([], $decorated->events());
        $this->assertSame(6, $decorated->version());
    }

    public function testContinuousListeningWithNumberOfEventsBeingMoreThanImposedLimit1()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $this->listener1
            ->expects($this->never())
            ->method('replay')
        ;

        $decorated = new Subscription($this->listener1, $this->clock);
        $subscription = new CommittingSubscription($decorated, $this->uow);

        $this->uow
            ->expects($this->exactly(6))
            ->method('commit')
        ;
        $this->uow
            ->expects($this->exactly(7))
            ->method('add')
            ->with($decorated)
        ;

        $this->assertSame($subscription->listener(), $this->listener1);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertEmpty($decorated->events());
        $this->assertSame(0, $decorated->version());

        $subscription->startFor($this->event1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertSame(0, $decorated->version());
        $this->assertEquals([new SubscriptionStarted($this->event1, $now)], $decorated->events());

        $decorated->commit();

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertSame(1, $decorated->version());
        $this->assertEquals([], $decorated->events());

        $this->stream1 = new InMemoryStream($this->event1, $this->event2);
        $this->stream2 = new InMemoryStream($this->event3, $this->event4, $this->event5);

        $this->store
            ->expects($this->exactly(2))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream1,
                $this->stream2
            )
        ;

        $this->listener1
            ->expects($this->exactly(4))
            ->method('on')
            ->withConsecutive(
                [$this->event1],
                [$this->event2],
                [$this->event3],
                [$this->event4]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false,
                false,
                true
            )
        ;
        $this->listener3
            ->expects($this->never())
            ->method('completed')
        ;

        $events = $subscription->subscribeTo($this->store, 4);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1, $this->event2, $this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionIgnoredEvent($this->event2, $now), new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now)], $decorated->events());
        $this->assertSame(1, $decorated->version());

        $decorated->commit();

        $this->assertEquals([], $decorated->events());
        $this->assertSame(5, $decorated->version());
    }

    public function testContinuousListeningWithNumberOfEventsBeingMoreThanImposedLimit2()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $this->listener1
            ->expects($this->never())
            ->method('replay')
        ;

        $decorated = new Subscription($this->listener1, $this->clock);
        $subscription = new CommittingSubscription($decorated, $this->uow);

        $this->uow
            ->expects($this->exactly(6))
            ->method('commit')
        ;
        $this->uow
            ->expects($this->exactly(7))
            ->method('add')
            ->with($decorated)
        ;

        $this->assertSame($subscription->listener(), $this->listener1);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertEmpty($decorated->events());
        $this->assertSame(0, $decorated->version());

        $subscription->startFor($this->event1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertSame(0, $decorated->version());
        $this->assertEquals([new SubscriptionStarted($this->event1, $now)], $decorated->events());

        $decorated->commit();

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertSame(1, $decorated->version());
        $this->assertEquals([], $decorated->events());

        $this->stream1 = new InMemoryStream($this->event1, $this->event2);
        $this->stream2 = new InMemoryStream($this->event3, $this->event4);

        $this->store
            ->expects($this->exactly(2))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream1,
                $this->stream2
            )
        ;

        $this->listener1
            ->expects($this->exactly(4))
            ->method('on')
            ->withConsecutive(
                [$this->event1],
                [$this->event2],
                [$this->event3],
                [$this->event4]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false,
                false,
                true
            )
        ;
        $this->listener3
            ->expects($this->never())
            ->method('completed')
        ;

        $events = $subscription->subscribeTo($this->store, 4);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1, $this->event2, $this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionIgnoredEvent($this->event2, $now), new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now)], $decorated->events());
        $this->assertSame(1, $decorated->version());

        $decorated->commit();

        $this->assertEquals([], $decorated->events());
        $this->assertSame(5, $decorated->version());
    }

    public function testContinuousListeningWithNumberOfEventsBeingLessThanImposedLimit1()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $this->listener1
            ->expects($this->never())
            ->method('replay')
        ;

        $decorated = new Subscription($this->listener1, $this->clock);
        $subscription = new CommittingSubscription($decorated, $this->uow);

        $this->uow
            ->expects($this->exactly(7))
            ->method('commit')
        ;
        $this->uow
            ->expects($this->exactly(8))
            ->method('add')
            ->with($decorated)
        ;

        $this->assertSame($subscription->listener(), $this->listener1);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertEmpty($decorated->events());
        $this->assertSame(0, $decorated->version());

        $subscription->startFor($this->event1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertSame(0, $decorated->version());
        $this->assertEquals([new SubscriptionStarted($this->event1, $now)], $decorated->events());

        $decorated->commit();

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertSame(1, $decorated->version());
        $this->assertEquals([], $decorated->events());

        $this->stream1 = new InMemoryStream($this->event1, $this->event2);
        $this->stream2 = new InMemoryStream($this->event3, $this->event4, $this->event5);
        $this->stream3 = new InMemoryStream();

        $this->store
            ->expects($this->exactly(3))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream1,
                $this->stream2,
                $this->stream3
            )
        ;

        $this->listener1
            ->expects($this->exactly(5))
            ->method('on')
            ->withConsecutive(
                [$this->event1],
                [$this->event2],
                [$this->event3],
                [$this->event4],
                [$this->event5]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false,
                false,
                true,
                true
            )
        ;
        $this->listener3
            ->expects($this->never())
            ->method('completed')
        ;

        $events = $subscription->subscribeTo($this->store, 6);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1, $this->event2, $this->event3, $this->event4, $this->event5], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionIgnoredEvent($this->event2, $now), new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now), new SubscriptionListenedToEvent($this->event5, $now)], $decorated->events());
        $this->assertSame(1, $decorated->version());

        $decorated->commit();

        $this->assertEquals([], $decorated->events());
        $this->assertSame(6, $decorated->version());
    }

    public function testCompletingListenerWhileContinuousListening()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener3
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;

        $decorated = new Subscription($this->listener3, $this->clock);
        $subscription = new CommittingSubscription($decorated, $this->uow);

        $this->uow
            ->expects($this->exactly(7))
            ->method('commit')
        ;
        $this->uow
            ->expects($this->exactly(8))
            ->method('add')
            ->with($decorated)
        ;

        $this->assertSame($subscription->listener(), $this->listener3);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertEmpty($decorated->events());
        $this->assertSame(0, $decorated->version());

        $subscription->startFor($this->event1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertSame(0, $decorated->version());
        $this->assertEquals([new SubscriptionStarted($this->event1, $now)], $decorated->events());

        $decorated->commit();

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $decorated->producerId());
        $this->assertNull($decorated->lastReplayed());
        $this->assertSame(1, $decorated->version());
        $this->assertEquals([], $decorated->events());

        $this->stream1 = new InMemoryStream($this->event1, $this->event2);
        $this->stream2 = new InMemoryStream($this->event3, $this->event4);
        $this->stream3 = new InMemoryStream($this->event5);

        $this->store
            ->expects($this->exactly(3))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream1,
                $this->stream2,
                $this->stream3
            )
        ;

        $this->listener3
            ->expects($this->exactly(5))
            ->method('on')
            ->withConsecutive(
                [$this->event1],
                [$this->event2],
                [$this->event3],
                [$this->event4],
                [$this->event5]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false,
                false,
                true,
                true
            )
        ;
        $this->listener3
            ->expects($this->exactly(5))
            ->method('completed')
            ->willReturnOnConsecutiveCalls(
                false,
                false,
                false,
                false,
                true
            )
        ;

        $events = $subscription->subscribeTo($this->store, 6);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1, $this->event2, $this->event3, $this->event4, $this->event5], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionIgnoredEvent($this->event2, $now), new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now), new SubscriptionListenedToEvent($this->event5, $now), new SubscriptionCompleted($now)], $decorated->events());
        $this->assertSame(1, $decorated->version());

        $decorated->commit();

        $this->assertEquals([], $decorated->events());
        $this->assertSame(7, $decorated->version());
    }
}
