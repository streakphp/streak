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

namespace Streak\Domain\Event\Sourced;

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
use Streak\Infrastructure\FixedClock;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Sourced\Subscription
 */
class SubscriptionTest extends TestCase
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

        $this->id1 = $this->getMockBuilder(Listener\Id::class)->getMockForAbstractClass();
        $this->id2 = $this->getMockBuilder(Listener\Id::class)->getMockForAbstractClass();

        $this->store = $this->getMockBuilder(EventStore::class)->getMockForAbstractClass();

        $this->stream1 = $this->getMockBuilder([Event\Stream::class, \IteratorAggregate::class])->getMock();
        $this->stream2 = $this->getMockBuilder([Event\Stream::class, \IteratorAggregate::class])->getMock();
        $this->stream3 = $this->getMockBuilder([Event\Stream::class, \IteratorAggregate::class])->getMock();

        $this->event1 = $this->getMockBuilder(Event::class)->setMockClassName('event1')->getMockForAbstractClass();
        $this->event2 = $this->getMockBuilder(Event::class)->setMockClassName('event2')->getMockForAbstractClass();
        $this->event3 = $this->getMockBuilder(Event::class)->setMockClassName('event3')->getMockForAbstractClass();
        $this->event4 = $this->getMockBuilder(Event::class)->setMockClassName('event4')->getMockForAbstractClass();
        $this->event5 = $this->getMockBuilder(Event::class)->setMockClassName('event5')->getMockForAbstractClass();

        $this->clock = new FixedClock(new \DateTime('2018-09-28 19:12:32.763188 +00:00'));
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

        $subscription = new Subscription($this->listener1, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener1);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertSame(0, $subscription->version());
        $this->assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        $this->assertEquals([new SubscriptionStarted($this->event1, $now)], $subscription->events());

        $subscription->commit();

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertSame(1, $subscription->version());
        $this->assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        $this->assertEquals([], $subscription->events());

        $this->store
            ->expects($this->exactly(3))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream1,
                $this->stream2,
                $this->stream3
            )
        ;

        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('without')
            ->willReturnSelf()
        ;
        $this->stream1
            ->expects($this->once())
            ->method('from')
            ->with($this->event1)
            ->willReturnSelf()
        ;
        $this->isIteratorFor($this->stream1, [$this->event1, $this->event2]);

        $this->stream2
            ->expects($this->atLeastOnce())
            ->method('without')
            ->willReturnSelf()
        ;
        $this->stream2
            ->expects($this->once())
            ->method('after')
            ->with($this->event2)
            ->willReturnSelf()
        ;
        $this->isIteratorFor($this->stream2, [$this->event3, $this->event4]);

        $this->stream3
            ->expects($this->atLeastOnce())
            ->method('without')
            ->willReturnSelf()
        ;
        $this->stream3
            ->expects($this->once())
            ->method('after')
            ->with($this->event4)
            ->willReturnSelf()
        ;
        $this->isIteratorFor($this->stream3, [$this->event5]);

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

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1, $this->event2], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, 2, $now), new SubscriptionIgnoredEvent($this->event2, 3, $now)], $subscription->events());
        $this->assertSame(1, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionIgnoredEvent($this->event3, 4, $now), new SubscriptionListenedToEvent($this->event4, 5, $now)], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event5], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event5, 6, $now)], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(6, $subscription->version());
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

        $subscription = new Subscription($this->listener7, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener7);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(0, $subscription->version());

        $subscription->startFor($this->event3);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertSame(0, $subscription->version());
        $this->assertEquals(new SubscriptionStarted($this->event3, $now), $subscription->lastEvent());
        $this->assertEquals([new SubscriptionStarted($this->event3, $now)], $subscription->events());

        $subscription->commit();

        $this->store
            ->expects($this->exactly(2))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream1,
                $this->stream2
            )
        ;

        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('without')
            ->willReturnSelf()
        ;
        $this->stream1
            ->expects($this->once())
            ->method('from')
            ->with($this->event2)
            ->willReturnSelf()
        ;
        $this->isIteratorFor($this->stream1, [$this->event2, $this->event3, $this->event4]);

        $this->stream2
            ->expects($this->atLeastOnce())
            ->method('without')
            ->willReturnSelf()
        ;
        $this->stream2
            ->expects($this->once())
            ->method('from')
            ->with($this->event1)
            ->willReturnSelf()
        ;
        $this->isIteratorFor($this->stream2, [$this->event1, $this->event2, $this->event3, $this->event4]);

        $this->listener7
            ->expects($this->exactly(7))
            ->method('on')
            ->withConsecutive(
                [$this->event2],
                [$this->event3],
                [$this->event4],
                // after restart
                [$this->event1],
                [$this->event2],
                [$this->event3],
                [$this->event4]
            )
            ->willReturn(true)
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event2, $this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event2, 2, $now), new SubscriptionListenedToEvent($this->event3, 3, $now), new SubscriptionListenedToEvent($this->event4, 4, $now)], $subscription->events());
        $this->assertSame(1, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(4, $subscription->version());

        $subscription->restart();

        $this->assertEquals([new SubscriptionRestarted($this->event3, 5, $now)], $subscription->events());
        $this->assertSame(4, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1, $this->event2, $this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, 6, $now), new SubscriptionListenedToEvent($this->event2, 7, $now), new SubscriptionListenedToEvent($this->event3, 8, $now), new SubscriptionListenedToEvent($this->event4, 9, $now)], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(9, $subscription->version());
    }

    public function testListenerWithFilterer()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener8
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $this->listener8
            ->expects($this->exactly(2))
            ->method('filter')
            ->withConsecutive([$this->stream1], [$this->stream2])
            ->willReturnOnConsecutiveCalls($this->stream1, $this->stream2)
        ;

        $subscription = new Subscription($this->listener8, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener8);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(0, $subscription->version());

        $subscription->startFor($this->event3);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertSame(0, $subscription->version());
        $this->assertEquals(new SubscriptionStarted($this->event3, $now), $subscription->lastEvent());
        $this->assertEquals([new SubscriptionStarted($this->event3, $now)], $subscription->events());

        $subscription->commit();

        $this->store
            ->expects($this->exactly(2))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream1,
                $this->stream2
            )
        ;

        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('without')
            ->willReturnSelf()
        ;
        $this->stream1
            ->expects($this->once())
            ->method('from')
            ->with($this->event3)
            ->willReturnSelf()
        ;
        $this->isIteratorFor($this->stream1, [$this->event2, $this->event3, $this->event4]);

        $this->stream2
            ->expects($this->atLeastOnce())
            ->method('without')
            ->willReturnSelf()
        ;
        $this->stream2
            ->expects($this->once())
            ->method('after')
            ->with($this->event4)
            ->willReturnSelf()
        ;
        $this->isIteratorFor($this->stream2, [$this->event1, $this->event2, $this->event3, $this->event4]);

        $this->listener8
            ->expects($this->exactly(7))
            ->method('on')
            ->withConsecutive(
                [$this->event2],
                [$this->event3],
                [$this->event4],
                // after restart
                [$this->event1],
                [$this->event2],
                [$this->event3],
                [$this->event4]
            )
            ->willReturn(true)
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event2, $this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event2, 2, $now), new SubscriptionListenedToEvent($this->event3, 3, $now), new SubscriptionListenedToEvent($this->event4, 4, $now)], $subscription->events());
        $this->assertSame(1, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(4, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1, $this->event2, $this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, 5, $now), new SubscriptionListenedToEvent($this->event2, 6, $now), new SubscriptionListenedToEvent($this->event3, 7, $now), new SubscriptionListenedToEvent($this->event4, 8, $now)], $subscription->events());
        $this->assertSame(4, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(8, $subscription->version());
    }

    public function testListenerWithPickerThatCanNotFindAnyEvent()
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
                $this->event3,
                $this->event3
            )
        ;

        $subscription = new Subscription($this->listener7, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener7);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(0, $subscription->version());

        $subscription->startFor($this->event3);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertSame(0, $subscription->version());
        $this->assertEquals(new SubscriptionStarted($this->event3, $now), $subscription->lastEvent());
        $this->assertEquals([new SubscriptionStarted($this->event3, $now)], $subscription->events());

        $subscription->commit();

        $this->store
            ->expects($this->exactly(2))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream1,
                $this->stream2
            )
        ;

        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('without')
            ->willReturnSelf()
        ;
        $this->stream1
            ->expects($this->once())
            ->method('from')
            ->with($this->event3)
            ->willReturnSelf()
        ;
        $this->isIteratorFor($this->stream1, [$this->event3, $this->event4]);

        $this->stream2
            ->expects($this->atLeastOnce())
            ->method('without')
            ->willReturnSelf()
        ;
        $this->stream2
            ->expects($this->once())
            ->method('from')
            ->with($this->event3)
            ->willReturnSelf()
        ;
        $this->isIteratorFor($this->stream2, [$this->event3, $this->event4]);

        $this->listener7
            ->expects($this->exactly(4))
            ->method('on')
            ->withConsecutive(
                [$this->event3],
                [$this->event4],
                // after restart
                [$this->event3],
                [$this->event4]
            )
            ->willReturn(true)
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event3, 2, $now), new SubscriptionListenedToEvent($this->event4, 3, $now)], $subscription->events());
        $this->assertSame(1, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $subscription->restart();

        $this->assertEquals([new SubscriptionRestarted($this->event3, 4, $now)], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(4, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event3, 5, $now), new SubscriptionListenedToEvent($this->event4, 6, $now)], $subscription->events());
        $this->assertSame(4, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(6, $subscription->version());
    }

    public function testTransactionalListenerWithoutReplaying()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener3
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener3, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener3);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertSame(0, $subscription->version());
        $this->assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        $this->assertEquals([new SubscriptionStarted($this->event1, $now)], $subscription->events());

        $subscription->commit();

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertSame(1, $subscription->version());
        $this->assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        $this->assertEquals([], $subscription->events());

        $this->store
            ->expects($this->exactly(3))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream1,
                $this->stream2,
                $this->stream3
            )
        ;

        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('without')
            ->willReturnSelf()
        ;
        $this->stream1
            ->expects($this->once())
            ->method('from')
            ->with($this->event1)
            ->willReturnSelf()
        ;
        $this->isIteratorFor($this->stream1, [$this->event1, $this->event2]);

        $this->stream2
            ->expects($this->atLeastOnce())
            ->method('without')
            ->willReturnSelf()
        ;
        $this->stream2
            ->expects($this->once())
            ->method('after')
            ->with($this->event2)
            ->willReturnSelf()
        ;
        $this->isIteratorFor($this->stream2, [$this->event3, $this->event4]);

        $this->stream3
            ->expects($this->atLeastOnce())
            ->method('without')
            ->willReturnSelf()
        ;
        $this->stream3
            ->expects($this->once())
            ->method('after')
            ->with($this->event4)
            ->willReturnSelf()
        ;
        $this->isIteratorFor($this->stream3, [$this->event5]);

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

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1, $this->event2], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, 2, $now), new SubscriptionIgnoredEvent($this->event2, 3, $now)], $subscription->events());
        $this->assertSame(1, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionIgnoredEvent($this->event3, 4, $now), new SubscriptionListenedToEvent($this->event4, 5, $now)], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event5], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event5, 6, $now)], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(6, $subscription->version());
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

        $subscription = new Subscription($this->listener1, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener1);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(0, $subscription->version());

        $event1 = new SubscriptionStarted($this->event1, $now);
        $event2 = new SubscriptionListenedToEvent($this->event1, 2, $now);
        $event3 = new SubscriptionListenedToEvent($this->event2, 3, $now);

        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('last')
            ->willReturn($event3)
        ;
        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('to')
            ->with($event3)
            ->willReturnSelf()
        ;
        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('only')
            ->with(SubscriptionStarted::class, SubscriptionRestarted::class, SubscriptionCompleted::class, SubscriptionListenedToEvent::class)
            ->willReturnSelf()
        ;
        $this->stream1
            ->expects($this->never())
            ->method('from')
            ->with($this->event1)
        ;
        $this->isIteratorFor($this->stream1, [$event1, $event2, $event3]);

        $subscription->replay($this->stream1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertSame(3, $subscription->version());
        $this->assertEquals($event3, $subscription->lastEvent());
        $this->assertEmpty($subscription->events());

        $this->store
            ->expects($this->exactly(2))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream2,
                $this->stream3
            )
        ;

        $this->stream2
            ->expects($this->atLeastOnce())
            ->method('without')
            ->willReturnSelf()
        ;
        $this->stream2
            ->expects($this->once())
            ->method('after')
            ->with($this->event2)
            ->willReturnSelf()
        ;
        $this->isIteratorFor($this->stream2, [$this->event3, $this->event4]);

        $this->stream3
            ->expects($this->atLeastOnce())
            ->method('without')
            ->willReturnSelf()
        ;
        $this->stream3
            ->expects($this->once())
            ->method('after')
            ->with($this->event4)
            ->willReturnSelf()
        ;
        $this->isIteratorFor($this->stream3, [$this->event5]);

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
        $this->assertEquals([new SubscriptionIgnoredEvent($this->event3, 4, $now), new SubscriptionListenedToEvent($this->event4, 5, $now)], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event5], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event5, 6, $now)], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(6, $subscription->version());
    }

    public function testReplayableListenerWithReplaying()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $subscription = new Subscription($this->listener2, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener2);
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(0, $subscription->version());

        $event1 = new SubscriptionStarted($this->event1, $now);
        $event2 = new SubscriptionListenedToEvent($this->event1, 2, $now);
        $event3 = new SubscriptionListenedToEvent($this->event2, 3, $now);

        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('last')
            ->willReturn($event3)
        ;
        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('to')
            ->with($event3)
            ->willReturnSelf()
        ;
        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('only')
            ->with(SubscriptionStarted::class, SubscriptionRestarted::class, SubscriptionCompleted::class, SubscriptionListenedToEvent::class)
            ->willReturnSelf()
        ;
        $this->isIteratorFor($this->stream1, [$event1, $event2, $event3]);

        $this->listener2
            ->expects($this->once())
            ->method('replay')
            ->with(new Event\Sourced\Subscription\Stream($this->stream1))
        ;

        $subscription->replay($this->stream1);

        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertSame(3, $subscription->version());
        $this->assertEquals($event3, $subscription->lastEvent());
        $this->assertEmpty($subscription->events());

        $this->store
            ->expects($this->exactly(2))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream2,
                $this->stream3
            )
        ;

        $this->stream2
            ->expects($this->atLeastOnce())
            ->method('without')
            ->willReturnSelf()
        ;
        $this->stream2
            ->expects($this->once())
            ->method('after')
            ->with($this->event2)
            ->willReturnSelf()
        ;
        $this->isIteratorFor($this->stream2, [$this->event3, $this->event4]);

        $this->stream3
            ->expects($this->atLeastOnce())
            ->method('without')
            ->willReturnSelf()
        ;
        $this->stream3
            ->expects($this->once())
            ->method('after')
            ->with($this->event4)
            ->willReturnSelf()
        ;
        $this->isIteratorFor($this->stream3, [$this->event5]);

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
        $this->assertEquals([new SubscriptionIgnoredEvent($this->event3, 4, $now), new SubscriptionListenedToEvent($this->event4, 5, $now)], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event5], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event5, 6, $now)], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(6, $subscription->version());
    }

    public function testReplayableListener()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');
        $subscription = new Subscription($this->listener2, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener2);
        $this->assertFalse($subscription->completed());
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(0, $subscription->version());

        $event1 = new SubscriptionStarted($this->event1, $now);
        $event2 = new SubscriptionListenedToEvent($this->event2, 2, $now);

        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('last')
            ->with()
            ->willReturn($event2)
        ;
        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('to')
            ->with($event2)
            ->willReturnSelf()
        ;
        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('only')
            ->with(SubscriptionStarted::class, SubscriptionRestarted::class, SubscriptionCompleted::class, SubscriptionListenedToEvent::class)
            ->willReturnSelf()
        ;

        $this->isIteratorFor($this->stream1, [$event1, $event2]);

        $this->listener2
            ->expects($this->once())
            ->method('replay')
            ->with(new Event\Sourced\Subscription\Stream($this->stream1))
        ;

        $subscription->replay($this->stream1);

        $this->assertSame($event2, $subscription->lastReplayed());
        $this->assertSame($event2, $subscription->lastEvent());
        $this->assertEmpty($subscription->events());
    }

    public function testTransactionalListener()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');
        $subscription = new Subscription($this->listener3, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener3);
        $this->assertFalse($subscription->completed());
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(0, $subscription->version());

        $event1 = new SubscriptionStarted($this->event1, $now);

        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('last')
            ->with()
            ->willReturn($event1)
        ;
        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('to')
            ->with($event1)
            ->willReturnSelf()
        ;
        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('only')
            ->with(SubscriptionStarted::class, SubscriptionRestarted::class, SubscriptionCompleted::class, SubscriptionListenedToEvent::class)
            ->willReturnSelf()
        ;

        $this->isIteratorFor($this->stream1, [$event1]);

        $subscription->replay($this->stream1);

        $this->assertSame($event1, $subscription->lastReplayed());
        $this->assertSame($event1, $subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(1, $subscription->version());
        $this->assertFalse($subscription->completed());

        $this->store
            ->expects($this->once())
            ->method('stream')
            ->willReturn($this->stream2)
        ;

        $this->stream2
            ->expects($this->once())
            ->method('from')
            ->with($this->event1)
            ->willReturnSelf()
        ;

        $this->stream2
            ->expects($this->atLeastOnce())
            ->method('without')
            ->willReturnSelf()
        ;

        $event2 = $this->event2;
        $event3 = $this->event3;
        $event4 = $this->event4;
        $events = [$event2, $event3, $event4];

        $this->isIteratorFor($this->stream2, $events);

        $this->listener3
            ->expects($this->exactly(2))
            ->method('on')
            ->withConsecutive(
                [$event2],
                [$event3]
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

        $this->assertEquals([$event2, $event3], iterator_to_array($events));
        $this->assertTrue($subscription->completed());
        $this->assertEquals([new SubscriptionListenedToEvent($event2, 2, $now), new SubscriptionListenedToEvent($event3, 3, $now), new SubscriptionCompleted(4, $now)], $subscription->events());
        $this->assertSame($event1, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionCompleted(4, $now), $subscription->lastEvent());
        $this->assertSame(1, $subscription->version());

        $subscription->commit();

        $this->assertTrue($subscription->completed());
        $this->assertEmpty($subscription->events());
        $this->assertSame(4, $subscription->version());
    }

    public function testStartingAlreadyStartedSubscription()
    {
        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener1, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener1);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());

        $subscription->startFor($this->event1);

        $this->expectExceptionObject(new Event\Subscription\Exception\SubscriptionAlreadyStarted($subscription));

        $subscription->startFor($this->event2);
    }

    public function testSubscribingAlreadyCompletedListener()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');
        $subscription = new Subscription($this->listener3, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener3);
        $this->assertFalse($subscription->completed());

        $event1 = new SubscriptionStarted($this->event1, $now);
        $event2 = new SubscriptionListenedToEvent($this->event2, 2, $now);
        $event3 = new SubscriptionCompleted(3, $now);

        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('last')
            ->with()
            ->willReturn($event3)
        ;
        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('to')
            ->with($event3)
            ->willReturnSelf()
        ;
        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('only')
            ->with(SubscriptionStarted::class, SubscriptionRestarted::class, SubscriptionCompleted::class, SubscriptionListenedToEvent::class)
            ->willReturnSelf()
        ;
        $this->isIteratorFor($this->stream1, [$event1, $event2, $event3]);

        $subscription->replay($this->stream1);

        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertSame($event3, $subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(3, $subscription->version());

        $this->store
            ->expects($this->never())
            ->method('stream')
        ;

        $this->stream2
            ->expects($this->never())
            ->method('from')
        ;

        $this->listener3
            ->expects($this->never())
            ->method('on')
        ;

        $this->listener3
            ->expects($this->never())
            ->method('completed')
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

        $subscription = new Subscription($this->listener1, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener1);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());

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
        $subscription = new Subscription($this->listener4, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener4);
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(0, $subscription->version());

        $this->listener4
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
        $event1 = new SubscriptionStarted($this->event2, $now);
        $event2 = new SubscriptionListenedToEvent($this->event2, 2, $now);
        $event3 = new SubscriptionListenedToEvent($this->event3, 3, $now);
        $event4 = new SubscriptionIgnoredEvent($this->event4, 4, $now);

        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('last')
            ->with()
            ->willReturn($event4)
        ;
        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('to')
            ->with($event4)
            ->willReturnSelf()
        ;
        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('only')
            ->with(SubscriptionStarted::class, SubscriptionRestarted::class, SubscriptionCompleted::class, SubscriptionListenedToEvent::class)
            ->willReturnSelf()
        ;

        $this->isIteratorFor($this->stream1, [$event1, $event2, $event3]);

        $subscription->replay($this->stream1);

        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertSame($event4, $subscription->lastEvent());
        $this->assertSame([], $subscription->events());
        $this->assertSame(4, $subscription->version());

        $subscription->restart();

        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionRestarted($this->event2, 5, $now), $subscription->lastEvent());
        $this->assertEquals([new SubscriptionRestarted($this->event2, 5, $now)], $subscription->events());
        $this->assertSame(4, $subscription->version());

        $subscription->commit();

        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionRestarted($this->event2, 5, $now), $subscription->lastEvent());
        $this->assertSame([], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $subscription->restart();

        // nothing changed as consecutive restarts are ignored
        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionRestarted($this->event2, 5, $now), $subscription->lastEvent());
        $this->assertSame([], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $this->store
            ->expects($this->exactly(1))
            ->method('stream')
            ->willReturnReference($this->stream3)
        ;

        $this->stream3
            ->expects($this->atLeastOnce())
            ->method('without')
            ->willReturnSelf()
        ;
        $this->stream3
            ->expects($this->once())
            ->method('from')
            ->with($this->event2)
            ->willReturnSelf()
        ;
        $this->isIteratorFor($this->stream3, [$this->event2, $this->event3, $this->event4, $this->event5]);

        $this->listener4
            ->expects($this->once())
            ->method('reset')
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertSame([$this->event2, $this->event3, $this->event4, $this->event5], $events);
        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionListenedToEvent($this->event5, 9, $now), $subscription->lastEvent());
        $this->assertEquals([new SubscriptionListenedToEvent($this->event2, 6, $now), new SubscriptionListenedToEvent($this->event3, 7, $now), new SubscriptionIgnoredEvent($this->event4, 8, $now), new SubscriptionListenedToEvent($this->event5, 9, $now)], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $subscription->commit();

        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionListenedToEvent($this->event5, 9, $now), $subscription->lastEvent());
        $this->assertSame([], $subscription->events());
        $this->assertSame(9, $subscription->version());
    }

    public function testRestartingSubscriptionForReplayableListener()
    {
        $subscription = new Subscription($this->listener5, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener5);
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(0, $subscription->version());

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
        $event1 = new SubscriptionStarted($this->event2, $now);
        $event2 = new SubscriptionListenedToEvent($this->event2, 2, $now);
        $event3 = new SubscriptionListenedToEvent($this->event3, 3, $now);
        $event4 = new SubscriptionIgnoredEvent($this->event4, 4, $now);

        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('last')
            ->with()
            ->willReturn($event4)
        ;
        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('to')
            ->with($event4)
            ->willReturnSelf()
        ;
        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('only')
            ->with(SubscriptionStarted::class, SubscriptionRestarted::class, SubscriptionCompleted::class, SubscriptionListenedToEvent::class)
            ->willReturnSelf()
        ;

        $this->isIteratorFor($this->stream1, [$event1, $event2, $event3]);

        $subscription->replay($this->stream1);

        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertSame($event4, $subscription->lastEvent());
        $this->assertSame([], $subscription->events());
        $this->assertSame(4, $subscription->version());

        $subscription->restart();

        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionRestarted($this->event2, 5, $now), $subscription->lastEvent());
        $this->assertEquals([new SubscriptionRestarted($this->event2, 5, $now)], $subscription->events());
        $this->assertSame(4, $subscription->version());

        $subscription->commit();

        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionRestarted($this->event2, 5, $now), $subscription->lastEvent());
        $this->assertSame([], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $subscription->restart();

        // nothing changed as consecutive restarts are ignored
        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionRestarted($this->event2, 5, $now), $subscription->lastEvent());
        $this->assertSame([], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $this->store
            ->expects($this->exactly(1))
            ->method('stream')
            ->willReturnReference($this->stream3)
        ;

        $this->stream3
            ->expects($this->atLeastOnce())
            ->method('without')
            ->willReturnSelf()
        ;
        $this->stream3
            ->expects($this->once())
            ->method('from')
            ->with($this->event2)
            ->willReturnSelf()
        ;
        $this->isIteratorFor($this->stream3, [$this->event2, $this->event3, $this->event4, $this->event5]);

        $this->listener5
            ->expects($this->once())
            ->method('reset')
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertSame([$this->event2, $this->event3, $this->event4, $this->event5], $events);
        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionListenedToEvent($this->event5, 9, $now), $subscription->lastEvent());
        $this->assertEquals([new SubscriptionListenedToEvent($this->event2, 6, $now), new SubscriptionListenedToEvent($this->event3, 7, $now), new SubscriptionIgnoredEvent($this->event4, 8, $now), new SubscriptionListenedToEvent($this->event5, 9, $now)], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $subscription->commit();

        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionListenedToEvent($this->event5, 9, $now), $subscription->lastEvent());
        $this->assertSame([], $subscription->events());
        $this->assertSame(9, $subscription->version());
    }

    public function testRestartingNotStartedSubscription()
    {
        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener1, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener1);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());

        $this->expectExceptionObject(new Event\Subscription\Exception\SubscriptionNotStartedYet($subscription));

        $subscription->restart();
    }

    public function testRestartingCompletedSubscription()
    {
        $subscription = new Subscription($this->listener6, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener6);
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(0, $subscription->version());
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
        $event1 = new SubscriptionStarted($this->event2, $now);
        $event2 = new SubscriptionListenedToEvent($this->event2, 2, $now);
        $event3 = new SubscriptionListenedToEvent($this->event3, 3, $now);
        $event4 = new SubscriptionCompleted(4, $now);

        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('last')
            ->with()
            ->willReturn($event4)
        ;
        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('to')
            ->with($event4)
            ->willReturnSelf()
        ;
        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('only')
            ->with(SubscriptionStarted::class, SubscriptionRestarted::class, SubscriptionCompleted::class, SubscriptionListenedToEvent::class)
            ->willReturnSelf()
        ;

        $this->isIteratorFor($this->stream1, [$event1, $event2, $event3, $event4]);

        $subscription->replay($this->stream1);

        $this->assertSame($event4, $subscription->lastReplayed());
        $this->assertSame($event4, $subscription->lastEvent());
        $this->assertSame([], $subscription->events());
        $this->assertSame(4, $subscription->version());
        $this->assertTrue($subscription->completed());

        $subscription->restart();

        $this->assertSame($event4, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionRestarted($this->event2, 5, $now), $subscription->lastEvent());
        $this->assertEquals([new SubscriptionRestarted($this->event2, 5, $now)], $subscription->events());
        $this->assertSame(4, $subscription->version());
        $this->assertFalse($subscription->completed());

        $subscription->commit();

        $this->assertSame($event4, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionRestarted($this->event2, 5, $now), $subscription->lastEvent());
        $this->assertSame([], $subscription->events());
        $this->assertSame(5, $subscription->version());
        $this->assertFalse($subscription->completed());

        $this->store
            ->expects($this->exactly(1))
            ->method('stream')
            ->willReturnReference($this->stream3)
        ;

        $this->stream3
            ->expects($this->atLeastOnce())
            ->method('without')
            ->willReturnSelf()
        ;
        $this->stream3
            ->expects($this->once())
            ->method('from')
            ->with($this->event2)
            ->willReturnSelf()
        ;
        $this->isIteratorFor($this->stream3, [$this->event2, $this->event3, $this->event4, $this->event5]);

        $this->listener6
            ->expects($this->once())
            ->method('reset')
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertSame([$this->event2, $this->event3, $this->event4, $this->event5], $events);
        $this->assertSame($event4, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionListenedToEvent($this->event5, 9, $now), $subscription->lastEvent());
        $this->assertEquals([new SubscriptionListenedToEvent($this->event2, 6, $now), new SubscriptionListenedToEvent($this->event3, 7, $now), new SubscriptionListenedToEvent($this->event4, 8, $now), new SubscriptionListenedToEvent($this->event5, 9, $now)], $subscription->events());
        $this->assertSame(5, $subscription->version());
        $this->assertFalse($subscription->completed());

        $subscription->commit();

        $this->assertSame($event4, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionListenedToEvent($this->event5, 9, $now), $subscription->lastEvent());
        $this->assertSame([], $subscription->events());
        $this->assertSame(9, $subscription->version());
        $this->assertFalse($subscription->completed());
    }

    public function testRestartingNonResettableListener()
    {
        $subscription = new Subscription($this->listener1, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener1);
        $this->assertFalse($subscription->completed());
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(0, $subscription->version());
        $this->assertFalse($subscription->completed());

        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');
        $event1 = new SubscriptionStarted($this->event1, $now);
        $event2 = new SubscriptionListenedToEvent($this->event2, 2, $now);
        $event3 = new SubscriptionListenedToEvent($this->event3, 3, $now);

        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('last')
            ->with()
            ->willReturn($event3)
        ;
        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('to')
            ->with($event3)
            ->willReturnSelf()
        ;
        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('only')
            ->with(SubscriptionStarted::class, SubscriptionRestarted::class, SubscriptionCompleted::class, SubscriptionListenedToEvent::class)
            ->willReturnSelf()
        ;

        $this->isIteratorFor($this->stream1, [$event1, $event2, $event3]);

        $subscription->replay($this->stream1);

        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertSame($event3, $subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(3, $subscription->version());
        $this->assertFalse($subscription->completed());

        $this->listener1
            ->expects($this->never())
            ->method('reset')
        ;

        $this->expectExceptionObject(new Event\Subscription\Exception\SubscriptionRestartNotPossible($subscription));

        $subscription->restart();
    }

    public function testEquals()
    {
        $subscription1 = new Subscription($this->listener1, $this->clock);

        $this->assertFalse($subscription1->equals(new \stdClass()));

        $subscription2 = new Subscription($this->listener2, $this->clock);

        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;

        $this->listener2
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id2)
        ;

        $this->id1
            ->expects($this->atLeastOnce())
            ->method('equals')
            ->with($this->id2)
            ->willReturn(true)
        ;

        $this->assertTrue($subscription1->equals($subscription2));

        $this->id2
            ->expects($this->atLeastOnce())
            ->method('equals')
            ->with($this->id2)
            ->willReturn(false)
        ;

        $this->assertFalse($subscription2->equals($subscription1));
    }

    public function testStartingSubscriptionWithResettableListenerWithFirstEventIgnored()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener4
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $subscription = new Subscription($this->listener4, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener4);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertSame(0, $subscription->version());
        $this->assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        $this->assertEquals([new SubscriptionStarted($this->event1, $now)], $subscription->events());

        $subscription->commit();

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertSame(1, $subscription->version());
        $this->assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        $this->assertEquals([], $subscription->events());

        $this->store
            ->expects($this->exactly(1))
            ->method('stream')
            ->willReturn($this->stream1)
        ;

        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('without')
            ->willReturnSelf()
        ;
        $this->stream1
            ->expects($this->once())
            ->method('from')
            ->with($this->event1)
            ->willReturnSelf()
        ;
        $this->isIteratorFor($this->stream1, [$this->event1]);

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
        $this->assertEquals([new SubscriptionIgnoredEvent($this->event1, 2, $now)], $subscription->events());
        $this->assertSame(1, $subscription->version());

        $subscription->commit();

        $this->assertEmpty($subscription->events());
        $this->assertSame(2, $subscription->version());
    }

    public function testStartingSubscriptionWithResettableListenerWithFirstEventNotIgnored()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener4
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $subscription = new Subscription($this->listener4, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener4);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertSame(0, $subscription->version());
        $this->assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        $this->assertEquals([new SubscriptionStarted($this->event1, $now)], $subscription->events());

        $subscription->commit();

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertSame(1, $subscription->version());
        $this->assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        $this->assertEquals([], $subscription->events());

        $this->store
            ->expects($this->exactly(1))
            ->method('stream')
            ->willReturn($this->stream1)
        ;

        $this->stream1
            ->expects($this->atLeastOnce())
            ->method('without')
            ->willReturnSelf()
        ;
        $this->stream1
            ->expects($this->once())
            ->method('from')
            ->with($this->event1)
            ->willReturnSelf()
        ;
        $this->isIteratorFor($this->stream1, [$this->event1]);

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
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, 2, $now)], $subscription->events());
        $this->assertSame(1, $subscription->version());

        $subscription->commit();

        $this->assertEmpty($subscription->events());
        $this->assertSame(2, $subscription->version());
    }

    public function testRestartingFreshlyStartedSubscription()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener4
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $subscription = new Subscription($this->listener4, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener4);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertSame(0, $subscription->version());
        $this->assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        $this->assertEquals([new SubscriptionStarted($this->event1, $now)], $subscription->events());

        $subscription->commit();

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertSame(1, $subscription->version());
        $this->assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        $this->assertEquals([], $subscription->events());

        $subscription->restart();

        // nothing changed as consecutive restarts are ignored
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertSame(1, $subscription->version());
        $this->assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        $this->assertEquals([], $subscription->events());
    }

    private function isIteratorFor(MockObject &$iterator, array $items)
    {
        $this->assertInstanceOf(\IteratorAggregate::class, $iterator);

        $internal = new \ArrayIterator($items);

        $iterator
            ->expects($this->atLeastOnce())
            ->method('getIterator')
            ->willReturn($internal)
        ;

        return $iterator;
    }
}
