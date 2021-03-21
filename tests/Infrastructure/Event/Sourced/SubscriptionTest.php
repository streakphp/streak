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
use Streak\Domain\Clock;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionCompleted;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionIgnoredEvent;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionListenedToEvent;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionListenersStateChanged;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionPaused;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionRestarted;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionStarted;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionUnPaused;
use Streak\Domain\EventStore;
use Streak\Domain\Id\UUID;
use Streak\Infrastructure\Event\InMemoryStream;
use Streak\Infrastructure\Event\Sourced\Subscription\InMemoryState;
use Streak\Infrastructure\Event\Sourced\SubscriptionTest\CompletableAndResettableListener;
use Streak\Infrastructure\Event\Sourced\SubscriptionTest\CompletableListener;
use Streak\Infrastructure\Event\Sourced\SubscriptionTest\FilteringListener;
use Streak\Infrastructure\Event\Sourced\SubscriptionTest\IterableStream;
use Streak\Infrastructure\Event\Sourced\SubscriptionTest\ReplayableAndResettableListener;
use Streak\Infrastructure\Event\Sourced\SubscriptionTest\ReplayableAndStatefulListener;
use Streak\Infrastructure\Event\Sourced\SubscriptionTest\ReplayableListener;
use Streak\Infrastructure\Event\Sourced\SubscriptionTest\ResettableListener;
use Streak\Infrastructure\Event\Sourced\SubscriptionTest\ResettableListenerThatCanPickStartingEvent;
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

    private ?Listener\State $state1 = null;

    private ?Listener\State $state2 = null;

    private ?Listener\State $state3 = null;

    private ?Clock $clock = null;

    public function setUp() : void
    {
        $this->listener1 = $this->getMockBuilder(Listener::class)->setMethods(['replay', 'reset', 'completed'])->getMockForAbstractClass();
        $this->listener2 = $this->getMockBuilder(ReplayableListener::class)->getMock();
        $this->listener3 = $this->getMockBuilder(CompletableListener::class)->getMock();
        $this->listener4 = $this->getMockBuilder(ResettableListener::class)->getMock();
        $this->listener5 = $this->getMockBuilder(ReplayableAndResettableListener::class)->getMock();
        $this->listener6 = $this->getMockBuilder(CompletableAndResettableListener::class)->getMock();
        $this->listener7 = $this->getMockBuilder(ResettableListenerThatCanPickStartingEvent::class)->getMock();
        $this->listener8 = $this->getMockBuilder(FilteringListener::class)->getMock();
        $this->listener9 = $this->getMockBuilder(ReplayableAndStatefulListener::class)->getMock();

        $this->id1 = new class('f5e65690-e50d-4312-a175-b004ec1bd42a') extends UUID implements Listener\Id {
        };
        $this->id2 = new class('d01286b0-7dd6-4520-b714-0e9903ab39af') extends UUID implements Listener\Id {
        };

        $this->store = $this->getMockBuilder(EventStore::class)->getMockForAbstractClass();

        $this->stream1 = $this->getMockBuilder(IterableStream::class)->getMock();
        $this->stream2 = $this->getMockBuilder(IterableStream::class)->getMock();
        $this->stream3 = $this->getMockBuilder(IterableStream::class)->getMock();

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
        $this->assertFalse($subscription->paused());

        $subscription->pause();
        $subscription->unpause();

        $subscription->startFor($this->event1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertSame(0, $subscription->version());
        $this->assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        $this->assertEquals([new SubscriptionStarted($this->event1, $now)], $subscription->events());
        $this->assertTrue($subscription->starting());
        $this->assertFalse($subscription->paused());

        $subscription->commit();

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertSame(1, $subscription->version());
        $this->assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        $this->assertEquals([], $subscription->events());
        $this->assertTrue($subscription->starting());
        $this->assertFalse($subscription->paused());

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
        $this->listener1
            ->expects($this->never())
            ->method('completed')
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1, $this->event2], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionIgnoredEvent($this->event2, $now)], $subscription->events());
        $this->assertSame(1, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now)], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(5, $subscription->version());
        $this->assertFalse($subscription->paused());

        $subscription->pause();

        $this->assertEquals([new SubscriptionPaused($this->clock->now())], $subscription->events());
        $this->assertSame(5, $subscription->version());
        $this->assertTrue($subscription->paused());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(6, $subscription->version());
        $this->assertTrue($subscription->paused());

        $subscription->pause();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(6, $subscription->version());
        $this->assertTrue($subscription->paused());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(6, $subscription->version());
        $this->assertTrue($subscription->paused());

        try {
            $events = $subscription->subscribeTo($this->store);
            iterator_to_array($events);
        } catch (Event\Subscription\Exception\SubscriptionPaused $exception) {
            $this->assertSame($subscription, $exception->subscription());
            $this->assertEquals([], $subscription->events());
            $this->assertSame(6, $subscription->version());
            $this->assertTrue($subscription->paused());
        } finally {
            $this->assertTrue(isset($exception));
        }

        $subscription->unpause();

        $this->assertEquals([new SubscriptionUnPaused($this->clock->now())], $subscription->events());
        $this->assertSame(6, $subscription->version());
        $this->assertFalse($subscription->paused());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(7, $subscription->version());
        $this->assertFalse($subscription->paused());

        $subscription->unpause();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(7, $subscription->version());
        $this->assertFalse($subscription->paused());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(7, $subscription->version());
        $this->assertFalse($subscription->paused());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event5], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event5, $now)], $subscription->events());
        $this->assertSame(7, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(8, $subscription->version());
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

        $events = $subscription->subscribeTo($this->store); // picker should pick $this->event2
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event2, $this->event3], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event2, $now), new SubscriptionListenedToEvent($this->event3, $now)], $subscription->events());
        $this->assertSame(1, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $subscription->restart();

        $this->assertEquals([new SubscriptionRestarted($this->event3, $now)], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(4, $subscription->version());

        $events = $subscription->subscribeTo($this->store); // after restart picker should pick $this->event1
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1, $this->event2, $this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionListenedToEvent($this->event2, $now), new SubscriptionListenedToEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now)], $subscription->events());
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

        $this->listener8
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;

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

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event2, $this->event3], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event2, $now), new SubscriptionListenedToEvent($this->event3, $now)], $subscription->events());
        $this->assertSame(1, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event4, $this->event5], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event4, $now), new SubscriptionListenedToEvent($this->event5, $now)], $subscription->events());
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

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1, $this->event2], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionIgnoredEvent($this->event2, $now)], $subscription->events());
        $this->assertSame(1, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now)], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $events = $subscription->subscribeTo($this->store, PHP_INT_MAX); // subscription will stop listening to #events right after it being completed, even if $limit was not exhausted.
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event5], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event5, $now), new SubscriptionCompleted($now)], $subscription->events());
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
        $event0 = Event\Envelope::new($event0, $this->id1, 1);
        $event1 = new SubscriptionListenedToEvent($this->event1, $now);
        $event1 = Event\Envelope::new($event1, $this->id1, 2);
        $event2 = new SubscriptionListenedToEvent($this->event2, $now);
        $event2 = Event\Envelope::new($event2, $this->id1, 3);

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

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now)], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event5], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event5, $now)], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(6, $subscription->version());
    }

    public function testReplayableListenerWithReplaying()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener2
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener2, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener2);
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(0, $subscription->version());

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

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now)], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event5], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event5, $now)], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(6, $subscription->version());
    }

    public function testReplayingStatefulListenerWithExistingState()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener9
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->with()
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener9, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener9);
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(0, $subscription->version());

        $event0 = new SubscriptionStarted($this->event1, $now);
        $event0 = Event\Envelope::new($event0, $this->id1, 1);
        $event1 = new SubscriptionListenedToEvent($this->event1, $now);
        $event1 = Event\Envelope::new($event1, $this->id1, 2);
        $event2 = new SubscriptionListenersStateChanged($this->state1, $now);
        $event2 = Event\Envelope::new($event2, $this->id1, 3);
        $event3 = new SubscriptionListenedToEvent($this->event2, $now);
        $event3 = Event\Envelope::new($event3, $this->id1, 4);

        $this->stream1 = new InMemoryStream($event0, $event1, $event2, $event3);
        $this->listener9
            ->expects($this->never())
            ->method('replay')
        ;
        $this->listener9
            ->expects($this->exactly(3))
            ->method('fromState')
            ->withConsecutive(
                [$this->callback(fn (Listener\State $state) => $state->equals($this->state1))],
                [$this->callback(fn (Listener\State $state) => $state->equals($this->state2))],
                [$this->callback(fn (Listener\State $state) => $state->equals($this->state3))]
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
            ->method('toState')
            ->with(InMemoryState::empty())
            ->willReturnOnConsecutiveCalls(
                $this->state1, // state not changed for $this->event3
                $this->state2, // state changed for $this->event4
                $this->state3  // state changed for $this->event5
            )
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now), new SubscriptionListenersStateChanged($this->state2, $now)], $subscription->events());
        $this->assertSame(4, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(7, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event5], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event5, $now), new SubscriptionListenersStateChanged($this->state3, $now)], $subscription->events());
        $this->assertSame(7, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(9, $subscription->version());
    }

    public function testReplayingStatefulListenerWithoutExistingState()
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener9
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->with()
            ->willReturn($this->id1)
        ;
        $subscription = new Subscription($this->listener9, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener9);
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(0, $subscription->version());

        $event0 = new SubscriptionStarted($this->event1, $now);
        $event0 = Event\Envelope::new($event0, $this->id1, 1);
        $event1 = new SubscriptionListenedToEvent($this->event1, $now);
        $event1 = Event\Envelope::new($event1, $this->id1, 2);
        $event2 = new SubscriptionListenedToEvent($this->event2, $now);
        $event2 = Event\Envelope::new($event2, $this->id1, 3);

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
                [$this->callback(fn (Listener\State $state) => $state->equals($this->state1))],
                [$this->callback(fn (Listener\State $state) => $state->equals($this->state2))],
                [$this->callback(fn (Listener\State $state) => $state->equals($this->state3))]
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
            ->method('toState')
            ->with(InMemoryState::empty())
            ->willReturnOnConsecutiveCalls(
                $this->state1, // state not changed for $this->event3
                $this->state2, // state changed for $this->event4
                $this->state3  // state changed for $this->event5
            )
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event3, $this->event4], $events);
        $this->assertEquals([new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenersStateChanged($this->state1, $now), new SubscriptionListenedToEvent($this->event4, $now), new SubscriptionListenersStateChanged($this->state2, $now)], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(7, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event5], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event5, $now), new SubscriptionListenersStateChanged($this->state3, $now)], $subscription->events());
        $this->assertSame(7, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(9, $subscription->version());
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

        $subscription = new Subscription($this->listener2, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener2);
        $this->assertFalse($subscription->completed());
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(0, $subscription->version());

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

        $subscription->replay($this->stream1);

        $this->assertSame($event2, $subscription->lastReplayed());
        $this->assertSame($event2, $subscription->lastEvent());
        $this->assertEmpty($subscription->events());
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

        $subscription = new Subscription($this->listener3, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener3);
        $this->assertFalse($subscription->completed());
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(0, $subscription->version());
        $this->assertFalse($subscription->paused());

        $event0 = new SubscriptionStarted($this->event1, $now);
        $event0 = Event\Envelope::new($event0, $this->id1, 1);

        $this->stream1 = new InMemoryStream($event0);

        $subscription->replay($this->stream1);

        $this->assertSame($event0, $subscription->lastReplayed());
        $this->assertSame($event0, $subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(1, $subscription->version());
        $this->assertFalse($subscription->completed());
        $this->assertFalse($subscription->paused());

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
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionListenedToEvent($this->event2, $now), new SubscriptionCompleted($now)], $subscription->events());
        $this->assertSame($event0, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionCompleted($now), $subscription->lastEvent());
        $this->assertSame(1, $subscription->version());
        $this->assertFalse($subscription->paused());

        $subscription->commit();

        $this->assertTrue($subscription->completed());
        $this->assertEmpty($subscription->events());
        $this->assertSame($event0, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionCompleted($now), $subscription->lastEvent());
        $this->assertSame(4, $subscription->version());
        $this->assertFalse($subscription->paused());

        $subscription->pause();

        $this->assertTrue($subscription->completed());
        $this->assertEmpty($subscription->events());
        $this->assertSame($event0, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionCompleted($now), $subscription->lastEvent());
        $this->assertSame(4, $subscription->version());
        $this->assertFalse($subscription->paused());

        $subscription->commit();

        $this->assertTrue($subscription->completed());
        $this->assertEmpty($subscription->events());
        $this->assertSame($event0, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionCompleted($now), $subscription->lastEvent());
        $this->assertSame(4, $subscription->version());
        $this->assertFalse($subscription->paused());

        $subscription->unpause();

        $this->assertTrue($subscription->completed());
        $this->assertEmpty($subscription->events());
        $this->assertSame($event0, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionCompleted($now), $subscription->lastEvent());
        $this->assertSame(4, $subscription->version());
        $this->assertFalse($subscription->paused());

        $subscription->commit();

        $this->assertTrue($subscription->completed());
        $this->assertEmpty($subscription->events());
        $this->assertSame($event0, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionCompleted($now), $subscription->lastEvent());
        $this->assertSame(4, $subscription->version());
        $this->assertFalse($subscription->paused());

        $this->expectExceptionObject(new Event\Subscription\Exception\SubscriptionAlreadyCompleted($subscription));

        $events = $subscription->subscribeTo($this->store);

        iterator_to_array($events);
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

        $this->listener3
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->with()
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener3, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener3);
        $this->assertFalse($subscription->completed());

        $event0 = new SubscriptionStarted($this->event1, $now);
        $event0 = Event\Envelope::new($event0, $this->id1, 1);
        $event1 = new SubscriptionListenedToEvent($this->event2, $now);
        $event1 = Event\Envelope::new($event1, $this->id1, 2);
        $event2 = new SubscriptionCompleted($now);
        $event2 = Event\Envelope::new($event2, $this->id1, 3);

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

        $subscription = new Subscription($this->listener3, $this->clock);

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
        $this->listener4
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->with()
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener4, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener4);
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->lastEvent());
        $this->assertEmpty($subscription->events());
        $this->assertSame(0, $subscription->version());

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

        $subscription->replay($this->stream1);

        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertSame($event3, $subscription->lastEvent());
        $this->assertSame([], $subscription->events());
        $this->assertSame(4, $subscription->version());

        $subscription->restart();

        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionRestarted($this->event1, $now), $subscription->lastEvent());
        $this->assertEquals([new SubscriptionRestarted($this->event1, $now)], $subscription->events());
        $this->assertSame(4, $subscription->version());

        $subscription->commit();

        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionRestarted($this->event1, $now), $subscription->lastEvent());
        $this->assertSame([], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $subscription->restart();

        // nothing changed as consecutive restarts are ignored
        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionRestarted($this->event1, $now), $subscription->lastEvent());
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

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertSame([$this->event1, $this->event3, $this->event4, $this->event5], $events);
        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionListenedToEvent($this->event5, $now), $subscription->lastEvent());
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionListenedToEvent($this->event3, $now), new SubscriptionIgnoredEvent($this->event4, $now), new SubscriptionListenedToEvent($this->event5, $now)], $subscription->events());
        $this->assertSame(5, $subscription->version());

        $subscription->commit();

        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionListenedToEvent($this->event5, $now), $subscription->lastEvent());
        $this->assertSame([], $subscription->events());
        $this->assertSame(9, $subscription->version());
    }

    public function testRestartingSubscriptionForReplayableListener()
    {
        $this->listener5
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->with()
            ->willReturn($this->id1)
        ;

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
        $event0 = Event\Envelope::new($event0, $this->id1, 1);
        $event1 = new SubscriptionListenedToEvent($this->event2, $now);
        $event1 = Event\Envelope::new($event1, $this->id1, 2);
        $event2 = new SubscriptionListenedToEvent($this->event3, $now);
        $event2 = Event\Envelope::new($event2, $this->id1, 3);

        $this->stream1 = new InMemoryStream($event0, $event1, $event2);

        $subscription->replay($this->stream1);

        $this->assertSame($event2, $subscription->lastReplayed());
        $this->assertSame($event2, $subscription->lastEvent());
        $this->assertSame([], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $subscription->restart();

        $this->assertSame($event2, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionRestarted($this->event2, $now), $subscription->lastEvent());
        $this->assertEquals([new SubscriptionRestarted($this->event2, $now)], $subscription->events());
        $this->assertSame(3, $subscription->version());

        $subscription->commit();

        $this->assertSame($event2, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionRestarted($this->event2, $now), $subscription->lastEvent());
        $this->assertSame([], $subscription->events());
        $this->assertSame(4, $subscription->version());

        $subscription->restart();

        // nothing changed as consecutive restarts are ignored
        $this->assertSame($event2, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionRestarted($this->event2, $now), $subscription->lastEvent());
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

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertSame([$this->event2, $this->event3, $this->event4, $this->event5], $events);
        $this->assertSame($event2, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionListenedToEvent($this->event5, $now), $subscription->lastEvent());
        $this->assertEquals([new SubscriptionListenedToEvent($this->event2, $now), new SubscriptionListenedToEvent($this->event3, $now), new SubscriptionIgnoredEvent($this->event4, $now), new SubscriptionListenedToEvent($this->event5, $now)], $subscription->events());
        $this->assertSame(4, $subscription->version());

        $subscription->commit();

        $this->assertSame($event2, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionListenedToEvent($this->event5, $now), $subscription->lastEvent());
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
        $this->listener6
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->with()
            ->willReturn($this->id1)
        ;

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
        $event0 = Event\Envelope::new($event0, $this->id1, 1);
        $event1 = new SubscriptionListenedToEvent($this->event2, $now);
        $event1 = Event\Envelope::new($event1, $this->id1, 2);
        $event2 = new SubscriptionListenedToEvent($this->event3, $now);
        $event2 = Event\Envelope::new($event2, $this->id1, 3);
        $event3 = new SubscriptionCompleted($now);
        $event3 = Event\Envelope::new($event3, $this->id1, 4);

        $this->stream1 = new InMemoryStream($event0, $event1, $event2, $event3);

        $subscription->replay($this->stream1);

        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertSame($event3, $subscription->lastEvent());
        $this->assertSame([], $subscription->events());
        $this->assertSame(4, $subscription->version());
        $this->assertTrue($subscription->completed());

        $subscription->restart();

        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionRestarted($this->event2, $now), $subscription->lastEvent());
        $this->assertEquals([new SubscriptionRestarted($this->event2, $now)], $subscription->events());
        $this->assertSame(4, $subscription->version());
        $this->assertFalse($subscription->completed());

        $subscription->commit();

        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionRestarted($this->event2, $now), $subscription->lastEvent());
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

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertSame([$this->event2, $this->event3, $this->event4, $this->event5], $events);
        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionListenedToEvent($this->event5, $now), $subscription->lastEvent());
        $this->assertEquals([new SubscriptionListenedToEvent($this->event2, $now), new SubscriptionListenedToEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now), new SubscriptionListenedToEvent($this->event5, $now)], $subscription->events());
        $this->assertSame(5, $subscription->version());
        $this->assertFalse($subscription->completed());

        $subscription->commit();

        $this->assertSame($event3, $subscription->lastReplayed());
        $this->assertEquals(new SubscriptionListenedToEvent($this->event5, $now), $subscription->lastEvent());
        $this->assertSame([], $subscription->events());
        $this->assertSame(9, $subscription->version());
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
        $event0 = Event\Envelope::new($event0, $this->id1, 1);
        $event1 = new SubscriptionListenedToEvent($this->event2, $now);
        $event1 = Event\Envelope::new($event1, $this->id1, 2);
        $event2 = new SubscriptionListenedToEvent($this->event3, $now);
        $event2 = Event\Envelope::new($event2, $this->id1, 3);

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
        $id1 = $this->getMockBuilder(Listener\Id::class)->setMockClassName('id1')->getMockForAbstractClass();
        $id2 = $this->getMockBuilder(Listener\Id::class)->setMockClassName('id2')->getMockForAbstractClass();

        $subscription1 = new Subscription($this->listener1, $this->clock);

        $this->assertFalse($subscription1->equals(new \stdClass()));

        $subscription2 = new Subscription($this->listener2, $this->clock);

        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($id1)
        ;

        $this->listener2
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($id2)
        ;

        $id1
            ->expects($this->atLeastOnce())
            ->method('equals')
            ->with($id2)
            ->willReturn(true)
        ;

        $this->assertTrue($subscription1->equals($subscription2));

        $id2
            ->expects($this->atLeastOnce())
            ->method('equals')
            ->with($id1)
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

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1], $events);
        $this->assertEquals([new SubscriptionIgnoredEvent($this->event1, $now)], $subscription->events());
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

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1], $events);
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, $now)], $subscription->events());
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
        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->with()
            ->willReturn($this->id1)
        ;

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
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionIgnoredEvent($this->event2, $now), new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now), new SubscriptionListenedToEvent($this->event5, $now)], $subscription->events());
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
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionIgnoredEvent($this->event2, $now), new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now)], $subscription->events());
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
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionIgnoredEvent($this->event2, $now), new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now)], $subscription->events());
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
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionIgnoredEvent($this->event2, $now), new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now), new SubscriptionListenedToEvent($this->event5, $now)], $subscription->events());
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
        $this->assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionIgnoredEvent($this->event2, $now), new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now), new SubscriptionListenedToEvent($this->event5, $now), new SubscriptionCompleted($now)], $subscription->events());
        $this->assertSame(1, $subscription->version());

        $subscription->commit();

        $this->assertEquals([], $subscription->events());
        $this->assertSame(7, $subscription->version());
    }
}

namespace Streak\Infrastructure\Event\Sourced\SubscriptionTest;

use Streak\Domain\Event;
use Streak\Domain\Event\Listener;

abstract class ReplayableListener implements Listener, Listener\Replayable
{
}

abstract class CompletableListener implements Listener, Listener\Completable
{
}

abstract class ResettableListener implements Listener, Listener\Resettable
{
}

abstract class ReplayableAndResettableListener implements Listener, Listener\Replayable, Listener\Resettable
{
}

abstract class CompletableAndResettableListener implements Listener, Listener\Completable, Listener\Resettable
{
}

abstract class ResettableListenerThatCanPickStartingEvent implements Listener, Listener\Resettable, Event\Picker
{
}

abstract class FilteringListener implements Listener, Event\Filterer
{
}

abstract class ReplayableAndStatefulListener implements Listener, Listener\Replayable, Listener\Stateful
{
}

abstract class IterableStream implements Event\Stream, \IteratorAggregate
{
}
