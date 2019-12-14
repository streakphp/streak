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

namespace Streak\Infrastructure\Event\Sourced;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionCompleted;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionIgnoredEvent;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionListenedToEvent;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionListenersStateChanged;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionRestarted;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionStarted;
use Streak\Domain\EventStore;
use Streak\Infrastructure\Event\InMemoryStream;
use Streak\Infrastructure\Event\Sourced\Subscription\InMemoryState;
use Streak\Infrastructure\FixedClock;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Event\Sourced\Subscription
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
     * @var Listener|Listener\Stateful|MockObject
     */
    private $listener9;

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
     * @var Listener\State
     */
    private $state1;

    /**
     * @var Listener\State
     */
    private $state2;

    /**
     * @var Listener\State
     */
    private $state3;

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
        $this->listener9 = $this->getMockBuilder([Listener::class, Listener\Replayable::class, Listener\Stateful::class])->getMock();

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

        $this->state1 = InMemoryState::fromArray(['number' => 1]);
        $this->state2 = InMemoryState::fromArray(['number' => 2]);
        $this->state3 = InMemoryState::fromArray(['number' => 3]);

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

        $events = $subscription->subscribeTo($this->store, 2);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1, $this->event2], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, 2, $now), new SubscriptionIgnoredEvent($this->event2, 3, $now)], $subscription->events());
        $this->assertSame(1, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $events = $subscription->subscribeTo($this->store, 2);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionIgnoredEvent($this->event3, 4, $now), new SubscriptionListenedToEvent($this->event4, 5, $now)], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $events = $subscription->subscribeTo($this->store, 1);
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

        $events = $subscription->subscribeTo($this->store, 2); // picker should pick $this->event2
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event2, $this->event3], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event2, 2, $now), new SubscriptionListenedToEvent($this->event3, 3, $now)], $subscription->events());
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

        $events = $subscription->subscribeTo($this->store, 4); // after restart picker should pick $this->event1
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1, $this->event2, $this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, 5, $now), new SubscriptionListenedToEvent($this->event2, 6, $now), new SubscriptionListenedToEvent($this->event3, 7, $now), new SubscriptionListenedToEvent($this->event4, 8, $now)], $subscription->events());
        $this->assertSame(4, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(8, $subscription->version());
    }

    public function testListenerWithFilterer()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->stream1 = new InMemoryStream($this->event1, $this->event2, $this->event3);
        $this->stream2 = new InMemoryStream($this->event1, $this->event2, $this->event3, $this->event4, $this->event5);

        $subscription = new Subscription($this->listener8, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener8);
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(0, $subscription->version());

        $subscription->startFor($this->event2);

        $this->assertNull($subscription->lastReplayed());
        $this->assertSame(0, $subscription->version());
        $this->assertEquals(new SubscriptionStarted($this->event2, $now), $subscription->lastEvent());
        $this->assertEquals([new SubscriptionStarted($this->event2, $now)], $subscription->events());

        $subscription->commit();

        $this->assertNull($subscription->lastReplayed());
        $this->assertSame(1, $subscription->version());
        $this->assertEquals(new SubscriptionStarted($this->event2, $now), $subscription->lastEvent());
        $this->assertEmpty($subscription->events());

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

        $events = $subscription->subscribeTo($this->store, 2);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event2, $this->event3], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event2, 2, $now), new SubscriptionListenedToEvent($this->event3, 3, $now)], $subscription->events());
        $this->assertSame(1, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $events = $subscription->subscribeTo($this->store, 2);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event4, $this->event5], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event4, 4, $now), new SubscriptionListenedToEvent($this->event5, 5, $now)], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(5, $subscription->version());
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

        $events = $subscription->subscribeTo($this->store, 2);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1, $this->event2], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, 2, $now), new SubscriptionIgnoredEvent($this->event2, 3, $now)], $subscription->events());
        $this->assertSame(1, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $events = $subscription->subscribeTo($this->store, 2);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionIgnoredEvent($this->event3, 4, $now), new SubscriptionListenedToEvent($this->event4, 5, $now)], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $events = $subscription->subscribeTo($this->store, PHP_INT_MAX); // subscription will stop listening to #events right after it being completed, even if $limit was not exhausted.
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event5], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event5, 6, $now), new SubscriptionCompleted(7, $now)], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(7, $subscription->version());
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

        $event0 = new SubscriptionStarted($this->event1, $now);
        $event1 = new SubscriptionListenedToEvent($this->event1, 2, $now);
        $event2 = new SubscriptionListenedToEvent($this->event2, 3, $now);

        $this->stream1 = new InMemoryStream($event0, $event1, $event2);

        $subscription->replay($this->stream1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertSame($event2, $subscription->lastReplayed());
        $this->assertSame(3, $subscription->version());
        $this->assertEquals($event2, $subscription->lastEvent());
        $this->assertEmpty($subscription->events());

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

        $events = $subscription->subscribeTo($this->store, 2);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionIgnoredEvent($this->event3, 4, $now), new SubscriptionListenedToEvent($this->event4, 5, $now)], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $events = $subscription->subscribeTo($this->store, 1);
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

        $event0 = new SubscriptionStarted($this->event1, $now);
        $event1 = new SubscriptionListenedToEvent($this->event1, 2, $now);
        $event2 = new SubscriptionListenedToEvent($this->event2, 3, $now);

        $this->stream1 = new InMemoryStream($event0, $event1, $event2);
        $this->listener2
            ->expects($this->once())
            ->method('replay')
            ->with($this->callback(function ($stream) {
                $stream = iterator_to_array($stream);

                return $this->equalTo([$this->event1, $this->event2])->evaluate($stream);
            }));

        $subscription->replay($this->stream1);

        $this->assertSame($event2, $subscription->lastReplayed());
        $this->assertSame(3, $subscription->version());
        $this->assertEquals($event2, $subscription->lastEvent());
        $this->assertEmpty($subscription->events());

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

        $events = $subscription->subscribeTo($this->store, 2);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionIgnoredEvent($this->event3, 4, $now), new SubscriptionListenedToEvent($this->event4, 5, $now)], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $events = $subscription->subscribeTo($this->store, 1);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event5], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event5, 6, $now)], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(6, $subscription->version());
    }

    public function testReplayingStatefulListenerWithExistingState()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $subscription = new Subscription($this->listener9, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener9);
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(0, $subscription->version());

        $event0 = new SubscriptionStarted($this->event1, $now);
        $event1 = new SubscriptionListenedToEvent($this->event1, 2, $now);
        $event2 = new SubscriptionListenersStateChanged($this->state1, 3, $now);
        $event3 = new SubscriptionListenedToEvent($this->event2, 4, $now);

        $this->stream1 = new InMemoryStream($event0, $event1, $event2, $event3);
        $this->listener9
            ->expects($this->never())
            ->method('replay')
        ;
        $this->listener9
            ->expects($this->exactly(3))
            ->method('fromState')
            ->withConsecutive(
                [$this->callback(function (Listener\State $state) { return $state->equals($this->state1); })],
                [$this->callback(function (Listener\State $state) { return $state->equals($this->state2); })],
                [$this->callback(function (Listener\State $state) { return $state->equals($this->state3); })]
            )
        ;

        $subscription->replay($this->stream1);

        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertSame(4, $subscription->version());
        $this->assertEquals($event3, $subscription->lastEvent());
        $this->assertEmpty($subscription->events());

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

        $this->listener9
            ->expects($this->exactly(3))
            ->method('on')
            ->withConsecutive(
                [$this->event3], // first subscribeTo() call
                [$this->event4], // first subscribeTo() call
                [$this->event5]  // second subscribeTo() call
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true,
                true
            )
        ;
        $this->listener9
            ->expects($this->any())
            ->method('toState')
            ->with(InMemoryState::empty())
            ->willReturnOnConsecutiveCalls(
                $this->state1, // state not changed for $this->event3
                $this->state2, // state changed for $this->event4
                $this->state3  // state changed for $this->event5
            )
        ;

        $events = $subscription->subscribeTo($this->store, 2);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionIgnoredEvent($this->event3, 5, $now), new SubscriptionListenedToEvent($this->event4, 6, $now), new SubscriptionListenersStateChanged($this->state2, 7, $now)], $subscription->events());
        $this->assertSame(4, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(7, $subscription->version());

        $events = $subscription->subscribeTo($this->store, 1);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event5], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event5, 8, $now), new SubscriptionListenersStateChanged($this->state3, 9, $now)], $subscription->events());
        $this->assertSame(7, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(9, $subscription->version());
    }

    public function testReplayingStatefulListenerWithoutExistingState()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $subscription = new Subscription($this->listener9, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener9);
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(0, $subscription->version());

        $event0 = new SubscriptionStarted($this->event1, $now);
        $event1 = new SubscriptionListenedToEvent($this->event1, 2, $now);
        $event2 = new SubscriptionListenedToEvent($this->event2, 3, $now);

        $this->stream1 = new InMemoryStream($event0, $event1, $event2);
        $this->listener9
            ->expects($this->once())
            ->method('replay')
            ->with($this->callback(function ($stream) {
                $stream = iterator_to_array($stream);

                return $this->equalTo([$this->event1, $this->event2])->evaluate($stream);
            }));

        $subscription->replay($this->stream1);

        $this->assertSame($event2, $subscription->lastReplayed());
        $this->assertSame(3, $subscription->version());
        $this->assertEquals($event2, $subscription->lastEvent());
        $this->assertEmpty($subscription->events());

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
        $this->listener9
            ->expects($this->exactly(3))
            ->method('fromState')
            ->withConsecutive(
                [$this->callback(function (Listener\State $state) { return $state->equals($this->state1); })],
                [$this->callback(function (Listener\State $state) { return $state->equals($this->state2); })],
                [$this->callback(function (Listener\State $state) { return $state->equals($this->state3); })]
            )
        ;
        $this->listener9
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
        $this->listener9
            ->expects($this->any())
            ->method('toState')
            ->with(InMemoryState::empty())
            ->willReturnOnConsecutiveCalls(
                $this->state1, // state not changed for $this->event3
                $this->state2, // state changed for $this->event4
                $this->state3  // state changed for $this->event5
            )
        ;

        $events = $subscription->subscribeTo($this->store, 2);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionIgnoredEvent($this->event3, 4, $now), new SubscriptionListenersStateChanged($this->state1, 5, $now), new SubscriptionListenedToEvent($this->event4, 6, $now), new SubscriptionListenersStateChanged($this->state2, 7, $now)], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(7, $subscription->version());

        $events = $subscription->subscribeTo($this->store, 1);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event5], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event5, 8, $now), new SubscriptionListenersStateChanged($this->state3, 9, $now)], $subscription->events());
        $this->assertSame(7, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(9, $subscription->version());
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

        $event0 = new SubscriptionStarted($this->event1, $now);
        $event1 = new SubscriptionListenedToEvent($this->event1, 2, $now);
        $event2 = new SubscriptionListenedToEvent($this->event2, 3, $now);

        $this->stream1 = new InMemoryStream($event0, $event1, $event2);

        $this->listener2
            ->expects($this->once())
            ->method('replay')
            ->with($this->callback(function ($stream) {
                $stream = iterator_to_array($stream);

                return $this->equalTo([$this->event1, $this->event2])->evaluate($stream);
            }));

        $subscription->replay($this->stream1);

        $this->assertSame($event2, $subscription->lastReplayed());
        $this->assertSame($event2, $subscription->lastEvent());
        $this->assertEmpty($subscription->events());
    }

    public function testCompletingListener()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');
        $subscription = new Subscription($this->listener3, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener3);
        $this->assertFalse($subscription->completed());
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(0, $subscription->version());

        $event0 = new SubscriptionStarted($this->event1, $now);

        $this->stream1 = new InMemoryStream($event0);

        $subscription->replay($this->stream1);

        $this->assertSame($event0, $subscription->lastReplayed());
        $this->assertSame($event0, $subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(1, $subscription->version());
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

        $events = $subscription->subscribeTo($this->store, 2);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1, $this->event2], $events);
        $this->assertTrue($subscription->completed());
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, 2, $now), new SubscriptionListenedToEvent($this->event2, 3, $now), new SubscriptionCompleted(4, $now)], $subscription->events());
        $this->assertSame($event0, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionCompleted(4, $now), $subscription->lastEvent());
        $this->assertSame(1, $subscription->version());

        $subscription->commit();

        $this->assertTrue($subscription->completed());
        $this->assertEmpty($subscription->events());
        $this->assertSame($event0, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionCompleted(4, $now), $subscription->lastEvent());
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

        $event0 = new SubscriptionStarted($this->event1, $now);
        $event1 = new SubscriptionListenedToEvent($this->event2, 2, $now);
        $event2 = new SubscriptionCompleted(3, $now);

        $this->stream1 = new InMemoryStream($event0, $event1, $event2);

        $subscription->replay($this->stream1);

        $this->assertTrue($subscription->completed());
        $this->assertSame($event2, $subscription->lastReplayed());
        $this->assertSame($event2, $subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(3, $subscription->version());

        $this->store
            ->expects($this->never())
            ->method('stream')
        ;

        $this->expectExceptionObject(new Event\Subscription\Exception\SubscriptionAlreadyCompleted($subscription));

        $events = $subscription->subscribeTo($this->store, 1);

        iterator_to_array($events);
    }

    public function testSubscribingRestartedAndCompletedListener()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');
        $subscription = new Subscription($this->listener3, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener3);
        $this->assertFalse($subscription->completed());

        $event0 = new SubscriptionStarted($this->event1, $now);
        $event1 = new SubscriptionListenedToEvent($this->event2, 2, $now);
        $event2 = new SubscriptionCompleted(3, $now);
        $event3 = new SubscriptionRestarted($this->event1, 4, $now);
        $event4 = new SubscriptionListenedToEvent($this->event2, 5, $now);
        $event5 = new SubscriptionCompleted(6, $now);

        $this->stream1 = new InMemoryStream($event0, $event1, $event2, $event3, $event4, $event5);

        $subscription->replay($this->stream1);

        $this->assertTrue($subscription->completed());
        $this->assertSame($event5, $subscription->lastReplayed());
        $this->assertSame($event5, $subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(6, $subscription->version());

        $this->store
            ->expects($this->never())
            ->method('stream')
        ;

        $this->expectExceptionObject(new Event\Subscription\Exception\SubscriptionAlreadyCompleted($subscription));

        $events = $subscription->subscribeTo($this->store, 1);

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

        $events = $subscription->subscribeTo($this->store, 1);

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

        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');
        $event0 = new SubscriptionStarted($this->event1, $now);
        $event1 = new SubscriptionListenedToEvent($this->event1, 2, $now);
        $event2 = new SubscriptionListenedToEvent($this->event2, 3, $now);
        $event3 = new SubscriptionIgnoredEvent($this->event3, 4, $now);

        $this->stream1 = new InMemoryStream($event0, $event1, $event2, $event3);

        $subscription->replay($this->stream1);

        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertSame($event3, $subscription->lastEvent());
        $this->assertSame([], $subscription->events());
        $this->assertSame(4, $subscription->version());

        $subscription->restart();

        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionRestarted($this->event1, 5, $now), $subscription->lastEvent());
        $this->assertEquals([new SubscriptionRestarted($this->event1, 5, $now)], $subscription->events());
        $this->assertSame(4, $subscription->version());

        $subscription->commit();

        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionRestarted($this->event1, 5, $now), $subscription->lastEvent());
        $this->assertSame([], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $subscription->restart();

        // nothing changed as consecutive restarts are ignored
        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionRestarted($this->event1, 5, $now), $subscription->lastEvent());
        $this->assertSame([], $subscription->events());
        $this->assertSame(5, $subscription->version());

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

        $events = $subscription->subscribeTo($this->store, 4);
        $events = iterator_to_array($events);

        $this->assertSame([$this->event1, $this->event3, $this->event4, $this->event5], $events);
        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionListenedToEvent($this->event5, 9, $now), $subscription->lastEvent());
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, 6, $now), new SubscriptionListenedToEvent($this->event3, 7, $now), new SubscriptionIgnoredEvent($this->event4, 8, $now), new SubscriptionListenedToEvent($this->event5, 9, $now)], $subscription->events());
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
        $event0 = new SubscriptionStarted($this->event2, $now);
        $event1 = new SubscriptionListenedToEvent($this->event2, 2, $now);
        $event2 = new SubscriptionListenedToEvent($this->event3, 3, $now);

        $this->stream1 = new InMemoryStream($event0, $event1, $event2);

        $subscription->replay($this->stream1);

        $this->assertSame($event2, $subscription->lastReplayed());
        $this->assertSame($event2, $subscription->lastEvent());
        $this->assertSame([], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $subscription->restart();

        $this->assertSame($event2, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionRestarted($this->event2, 4, $now), $subscription->lastEvent());
        $this->assertEquals([new SubscriptionRestarted($this->event2, 4, $now)], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $subscription->commit();

        $this->assertSame($event2, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionRestarted($this->event2, 4, $now), $subscription->lastEvent());
        $this->assertSame([], $subscription->events());
        $this->assertSame(4, $subscription->version());

        $subscription->restart();

        // nothing changed as consecutive restarts are ignored
        $this->assertSame($event2, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionRestarted($this->event2, 4, $now), $subscription->lastEvent());
        $this->assertSame([], $subscription->events());
        $this->assertSame(4, $subscription->version());

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

        $events = $subscription->subscribeTo($this->store, 4);
        $events = iterator_to_array($events);

        $this->assertSame([$this->event2, $this->event3, $this->event4, $this->event5], $events);
        $this->assertSame($event2, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionListenedToEvent($this->event5, 8, $now), $subscription->lastEvent());
        $this->assertEquals([new SubscriptionListenedToEvent($this->event2, 5, $now), new SubscriptionListenedToEvent($this->event3, 6, $now), new SubscriptionIgnoredEvent($this->event4, 7, $now), new SubscriptionListenedToEvent($this->event5, 8, $now)], $subscription->events());
        $this->assertSame(4, $subscription->version());

        $subscription->commit();

        $this->assertSame($event2, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionListenedToEvent($this->event5, 8, $now), $subscription->lastEvent());
        $this->assertSame([], $subscription->events());
        $this->assertSame(8, $subscription->version());
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
        $event0 = new SubscriptionStarted($this->event2, $now);
        $event1 = new SubscriptionListenedToEvent($this->event2, 2, $now);
        $event2 = new SubscriptionListenedToEvent($this->event3, 3, $now);
        $event3 = new SubscriptionCompleted(4, $now);

        $this->stream1 = new InMemoryStream($event0, $event1, $event2, $event3);

        $subscription->replay($this->stream1);

        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertSame($event3, $subscription->lastEvent());
        $this->assertSame([], $subscription->events());
        $this->assertSame(4, $subscription->version());
        $this->assertTrue($subscription->completed());

        $subscription->restart();

        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionRestarted($this->event2, 5, $now), $subscription->lastEvent());
        $this->assertEquals([new SubscriptionRestarted($this->event2, 5, $now)], $subscription->events());
        $this->assertSame(4, $subscription->version());
        $this->assertFalse($subscription->completed());

        $subscription->commit();

        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionRestarted($this->event2, 5, $now), $subscription->lastEvent());
        $this->assertSame([], $subscription->events());
        $this->assertSame(5, $subscription->version());
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

        $events = $subscription->subscribeTo($this->store, 4);
        $events = iterator_to_array($events);

        $this->assertSame([$this->event2, $this->event3, $this->event4, $this->event5], $events);
        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionListenedToEvent($this->event5, 9, $now), $subscription->lastEvent());
        $this->assertEquals([new SubscriptionListenedToEvent($this->event2, 6, $now), new SubscriptionListenedToEvent($this->event3, 7, $now), new SubscriptionListenedToEvent($this->event4, 8, $now), new SubscriptionListenedToEvent($this->event5, 9, $now)], $subscription->events());
        $this->assertSame(5, $subscription->version());
        $this->assertFalse($subscription->completed());

        $subscription->commit();

        $this->assertSame($event3, $subscription->lastReplayed());
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
        $event0 = new SubscriptionStarted($this->event1, $now);
        $event1 = new SubscriptionListenedToEvent($this->event2, 2, $now);
        $event2 = new SubscriptionListenedToEvent($this->event3, 3, $now);

        $this->stream1 = new InMemoryStream($event0, $event1, $event2);

        $subscription->replay($this->stream1);

        $this->assertSame($event2, $subscription->lastReplayed());
        $this->assertSame($event2, $subscription->lastEvent());
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

        $events = $subscription->subscribeTo($this->store, 1);
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

        $events = $subscription->subscribeTo($this->store, 1);
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

    public function testNonPositiveLimitGivenWhileSubscribingToTheEventStoreBeforeStarting()
    {
        $subscription = new Subscription($this->listener1, $this->clock);

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
        $subscription = new Subscription($this->listener1, $this->clock);
        $subscription->startFor($this->event1);
        $subscription->commit();

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
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, 2, $now), new SubscriptionIgnoredEvent($this->event2, 3, $now), new SubscriptionIgnoredEvent($this->event3, 4, $now), new SubscriptionListenedToEvent($this->event4, 5, $now), new SubscriptionListenedToEvent($this->event5, 6, $now)], $subscription->events());
        $this->assertSame(1, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(6, $subscription->version());
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
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, 2, $now), new SubscriptionIgnoredEvent($this->event2, 3, $now), new SubscriptionIgnoredEvent($this->event3, 4, $now), new SubscriptionListenedToEvent($this->event4, 5, $now)], $subscription->events());
        $this->assertSame(1, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(5, $subscription->version());
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
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, 2, $now), new SubscriptionIgnoredEvent($this->event2, 3, $now), new SubscriptionIgnoredEvent($this->event3, 4, $now), new SubscriptionListenedToEvent($this->event4, 5, $now)], $subscription->events());
        $this->assertSame(1, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(5, $subscription->version());
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
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, 2, $now), new SubscriptionIgnoredEvent($this->event2, 3, $now), new SubscriptionIgnoredEvent($this->event3, 4, $now), new SubscriptionListenedToEvent($this->event4, 5, $now), new SubscriptionListenedToEvent($this->event5, 6, $now)], $subscription->events());
        $this->assertSame(1, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(6, $subscription->version());
    }

    public function testCompletingListenerWhileContinuousListening()
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
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, 2, $now), new SubscriptionIgnoredEvent($this->event2, 3, $now), new SubscriptionIgnoredEvent($this->event3, 4, $now), new SubscriptionListenedToEvent($this->event4, 5, $now), new SubscriptionListenedToEvent($this->event5, 6, $now), new SubscriptionCompleted(7, $now)], $subscription->events());
        $this->assertSame(1, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(7, $subscription->version());
    }
}
