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

namespace Streak\Infrastructure\Domain\Event\Sourced;

use PHPUnit\Framework\TestCase;
use Streak\Domain\Clock;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Domain\EventStore;
use Streak\Domain\Id\UUID;
use Streak\Infrastructure\Domain\Clock\FixedClock;
use Streak\Infrastructure\Domain\Event\InMemoryStream;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionCompleted;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionIgnoredEvent;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionListenedToEvent;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionListenersStateChanged;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionPaused;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionRestarted;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionStarted;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionUnPaused;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\InMemoryState;
use Streak\Infrastructure\Domain\Event\Sourced\SubscriptionTest\CompletableAndResettableListener;
use Streak\Infrastructure\Domain\Event\Sourced\SubscriptionTest\CompletableListener;
use Streak\Infrastructure\Domain\Event\Sourced\SubscriptionTest\FilteringListener;
use Streak\Infrastructure\Domain\Event\Sourced\SubscriptionTest\IterableStream;
use Streak\Infrastructure\Domain\Event\Sourced\SubscriptionTest\ResettableListener;
use Streak\Infrastructure\Domain\Event\Sourced\SubscriptionTest\ResettableListenerThatCanPickStartingEvent;
use Streak\Infrastructure\Domain\Event\Sourced\SubscriptionTest\StatefulListener;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\Event\Sourced\Subscription
 */
class SubscriptionTest extends TestCase
{
    private Listener $listener1;
    private CompletableListener $listener3;
    private ResettableListener $listener4;
    private CompletableAndResettableListener $listener6;
    private ResettableListenerThatCanPickStartingEvent $listener7;
    private FilteringListener $listener8;
    private StatefulListener $listener9;

    private Listener\Id $id1;

    private EventStore $store;

    private Event\Stream $stream1;
    private Event\Stream $stream2;
    private Event\Stream $stream3;

    private Event\Envelope $event1;
    private Event\Envelope $event2;
    private Event\Envelope $event3;
    private Event\Envelope $event4;
    private Event\Envelope $event5;

    private Listener\State $state1;
    private Listener\State $state2;
    private Listener\State $state3;

    private Clock $clock;

    protected function setUp(): void
    {
        $this->listener1 = $this->getMockBuilder(Listener::class)->addMethods(['replay', 'reset', 'completed'])->getMockForAbstractClass();
        $this->listener3 = $this->getMockBuilder(CompletableListener::class)->getMock();
        $this->listener4 = $this->getMockBuilder(ResettableListener::class)->getMock();
        $this->listener6 = $this->getMockBuilder(CompletableAndResettableListener::class)->getMock();
        $this->listener7 = $this->getMockBuilder(ResettableListenerThatCanPickStartingEvent::class)->getMock();
        $this->listener8 = $this->getMockBuilder(FilteringListener::class)->getMock();
        $this->listener9 = $this->getMockBuilder(StatefulListener::class)->getMock();

        $this->id1 = new class('f5e65690-e50d-4312-a175-b004ec1bd42a') extends UUID implements Listener\Id {
        };

        $this->store = $this->getMockBuilder(EventStore::class)->getMockForAbstractClass();

        $this->stream1 = $this->getMockBuilder(IterableStream::class)->getMock();
        $this->stream2 = $this->getMockBuilder(IterableStream::class)->getMock();
        $this->stream3 = $this->getMockBuilder(IterableStream::class)->getMock();

        $this->event1 = Event\Envelope::new($this->getMockBuilder(Event::class)->setMockClassName('event1')->getMockForAbstractClass(), UUID::random());
        $this->event2 = Event\Envelope::new($this->getMockBuilder(Event::class)->setMockClassName('event2')->getMockForAbstractClass(), UUID::random());
        $this->event3 = Event\Envelope::new($this->getMockBuilder(Event::class)->setMockClassName('event3')->getMockForAbstractClass(), UUID::random());
        $this->event4 = Event\Envelope::new($this->getMockBuilder(Event::class)->setMockClassName('event4')->getMockForAbstractClass(), UUID::random());
        $this->event5 = Event\Envelope::new($this->getMockBuilder(Event::class)->setMockClassName('event5')->getMockForAbstractClass(), UUID::random());

        $this->state1 = InMemoryState::fromArray(['number' => 1]);
        $this->state2 = InMemoryState::fromArray(['number' => 2]);
        $this->state3 = InMemoryState::fromArray(['number' => 3]);

        $this->clock = new FixedClock(new \DateTime('2018-09-28 19:12:32.763188 +00:00'));
    }

    public function testListener(): void
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener1
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $this->listener1
            ->expects(self::never())
            ->method('replay')
        ;

        $subscription = new Subscription($this->listener1, $this->clock);

        self::assertSame($subscription->listener(), $this->listener1);
        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertNull($subscription->lastEvent());
        self::assertEmpty($subscription->events());
        self::assertSame(0, $subscription->version());
        self::assertFalse($subscription->paused());

        $subscription->pause();
        $subscription->unpause();

        $subscription->startFor($this->event1);

        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertSame(0, $subscription->version());
        self::assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        self::assertEquals([new SubscriptionStarted($this->event1, $now)], $subscription->events());
        self::assertTrue($subscription->starting());
        self::assertFalse($subscription->paused());

        $subscription->commit();

        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertSame(1, $subscription->version());
        self::assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        self::assertEquals([], $subscription->events());
        self::assertTrue($subscription->starting());
        self::assertFalse($subscription->paused());

        $this->stream1 = new InMemoryStream($this->event1, $this->event2);
        $this->stream2 = new InMemoryStream($this->event3, $this->event4);
        $this->stream3 = new InMemoryStream($this->event5);

        $this->store
            ->expects(self::exactly(3))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream1,
                $this->stream2,
                $this->stream3
            )
        ;

        $this->listener1
            ->expects(self::exactly(5))
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
            ->expects(self::never())
            ->method('completed')
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event1, $this->event2], $events);
        self::assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionIgnoredEvent($this->event2, $now)], $subscription->events());
        self::assertSame(1, $subscription->version());

        $subscription->commit();

        self::assertEquals([], $subscription->events());
        self::assertSame(3, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event3, $this->event4], $events);
        self::assertEquals([new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now)], $subscription->events());
        self::assertSame(3, $subscription->version());

        $subscription->commit();

        self::assertEquals([], $subscription->events());
        self::assertSame(5, $subscription->version());
        self::assertFalse($subscription->paused());

        $subscription->pause();

        self::assertEquals([new SubscriptionPaused($this->clock->now())], $subscription->events());
        self::assertSame(5, $subscription->version());
        self::assertTrue($subscription->paused());

        $subscription->commit();

        self::assertEquals([], $subscription->events());
        self::assertSame(6, $subscription->version());
        self::assertTrue($subscription->paused());

        $subscription->pause();

        self::assertEquals([], $subscription->events());
        self::assertSame(6, $subscription->version());
        self::assertTrue($subscription->paused());

        $subscription->commit();

        self::assertEquals([], $subscription->events());
        self::assertSame(6, $subscription->version());
        self::assertTrue($subscription->paused());

        try {
            $events = $subscription->subscribeTo($this->store);
            iterator_to_array($events);
            self::fail();
        } catch (Event\Subscription\Exception\SubscriptionPaused $exception) {
            self::assertSame($subscription, $exception->subscription());
            self::assertEquals([], $subscription->events());
            self::assertSame(6, $subscription->version());
            self::assertTrue($subscription->paused());
        }

        $subscription->unpause();

        self::assertEquals([new SubscriptionUnPaused($this->clock->now())], $subscription->events());
        self::assertSame(6, $subscription->version());
        self::assertFalse($subscription->paused());

        $subscription->commit();

        self::assertEquals([], $subscription->events());
        self::assertSame(7, $subscription->version());
        self::assertFalse($subscription->paused());

        $subscription->unpause();

        self::assertEquals([], $subscription->events());
        self::assertSame(7, $subscription->version());
        self::assertFalse($subscription->paused());

        $subscription->commit();

        self::assertEquals([], $subscription->events());
        self::assertSame(7, $subscription->version());
        self::assertFalse($subscription->paused());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event5], $events);
        self::assertEquals([new SubscriptionListenedToEvent($this->event5, $now)], $subscription->events());
        self::assertSame(7, $subscription->version());

        $subscription->commit();

        self::assertEquals([], $subscription->events());
        self::assertSame(8, $subscription->version());
    }

    public function testListenerWithPicker(): void
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener7
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $this->listener7
            ->expects(self::exactly(2))
            ->method('pick')
            ->willReturnOnConsecutiveCalls(
                $this->event2,
                $this->event1 // let's change starting point after restart
            )
        ;

        $subscription = new Subscription($this->listener7, $this->clock);

        self::assertSame($subscription->listener(), $this->listener7);
        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertNull($subscription->lastEvent());
        self::assertEmpty($subscription->events());
        self::assertSame(0, $subscription->version());

        $subscription->startFor($this->event3);

        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertSame(0, $subscription->version());
        self::assertEquals(new SubscriptionStarted($this->event3, $now), $subscription->lastEvent());
        self::assertEquals([new SubscriptionStarted($this->event3, $now)], $subscription->events());

        $subscription->commit();

        $this->stream1 = new InMemoryStream($this->event1, $this->event2, $this->event3);
        $this->stream2 = new InMemoryStream($this->event1, $this->event2, $this->event3, $this->event4);

        $this->store
            ->expects(self::exactly(2))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream1,
                $this->stream2
            )
        ;

        $this->listener7
            ->expects(self::exactly(6))
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

        self::assertEquals([$this->event2, $this->event3], $events);
        self::assertEquals([new SubscriptionListenedToEvent($this->event2, $now), new SubscriptionListenedToEvent($this->event3, $now)], $subscription->events());
        self::assertSame(1, $subscription->version());

        $subscription->commit();

        self::assertEquals([], $subscription->events());
        self::assertSame(3, $subscription->version());

        $subscription->restart();

        self::assertEquals([new SubscriptionRestarted($this->event3, $now)], $subscription->events());
        self::assertSame(3, $subscription->version());

        $subscription->commit();

        self::assertEquals([], $subscription->events());
        self::assertSame(4, $subscription->version());

        $events = $subscription->subscribeTo($this->store); // after restart picker should pick $this->event1
        $events = iterator_to_array($events);

        self::assertEquals([$this->event1, $this->event2, $this->event3, $this->event4], $events);
        self::assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionListenedToEvent($this->event2, $now), new SubscriptionListenedToEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now)], $subscription->events());
        self::assertSame(4, $subscription->version());

        $subscription->commit();

        self::assertEquals([], $subscription->events());
        self::assertSame(8, $subscription->version());
    }

    public function testListenerWithFilterer(): void
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->stream1 = new InMemoryStream($this->event1, $this->event2, $this->event3);
        $this->stream2 = new InMemoryStream($this->event1, $this->event2, $this->event3, $this->event4, $this->event5);

        $this->listener8
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener8, $this->clock);

        self::assertSame($subscription->listener(), $this->listener8);
        self::assertNull($subscription->lastReplayed());
        self::assertNull($subscription->lastEvent());
        self::assertEmpty($subscription->events());
        self::assertSame(0, $subscription->version());

        $subscription->startFor($this->event2);

        self::assertNull($subscription->lastReplayed());
        self::assertSame(0, $subscription->version());
        self::assertEquals(new SubscriptionStarted($this->event2, $now), $subscription->lastEvent());
        self::assertEquals([new SubscriptionStarted($this->event2, $now)], $subscription->events());

        $subscription->commit();

        self::assertNull($subscription->lastReplayed());
        self::assertSame(1, $subscription->version());
        self::assertEquals(new SubscriptionStarted($this->event2, $now), $subscription->lastEvent());
        self::assertEmpty($subscription->events());

        $this->store
            ->expects(self::exactly(2))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream1,
                $this->stream2
            )
        ;

        $this->listener8
            ->expects(self::exactly(2))
            ->method('filter')
            ->withConsecutive(
                [self::equalTo((new InMemoryStream($this->event1, $this->event2, $this->event3))->without(SubscriptionStarted::class, SubscriptionListenedToEvent::class, SubscriptionIgnoredEvent::class, SubscriptionCompleted::class, SubscriptionRestarted::class))],
                [self::equalTo((new InMemoryStream($this->event1, $this->event2, $this->event3, $this->event4, $this->event5))->without(SubscriptionStarted::class, SubscriptionListenedToEvent::class, SubscriptionIgnoredEvent::class, SubscriptionCompleted::class, SubscriptionRestarted::class))]
            )->willReturnOnConsecutiveCalls(
                $this->stream1,
                $this->stream2
            )
        ;
        $this->listener8
            ->expects(self::exactly(4))
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

        self::assertEquals([$this->event2, $this->event3], $events);
        self::assertEquals([new SubscriptionListenedToEvent($this->event2, $now), new SubscriptionListenedToEvent($this->event3, $now)], $subscription->events());
        self::assertSame(1, $subscription->version());

        $subscription->commit();

        self::assertEquals([], $subscription->events());
        self::assertSame(3, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event4, $this->event5], $events);
        self::assertEquals([new SubscriptionListenedToEvent($this->event4, $now), new SubscriptionListenedToEvent($this->event5, $now)], $subscription->events());
        self::assertSame(3, $subscription->version());

        $subscription->commit();

        self::assertEquals([], $subscription->events());
        self::assertSame(5, $subscription->version());
    }

    public function testTransactionalListenerWithoutReplaying(): void
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener3
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $this->listener1
            ->expects(self::never())
            ->method('replay')
        ;

        $subscription = new Subscription($this->listener3, $this->clock);

        self::assertSame($subscription->listener(), $this->listener3);
        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertNull($subscription->lastEvent());
        self::assertEmpty($subscription->events());
        self::assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertSame(0, $subscription->version());
        self::assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        self::assertEquals([new SubscriptionStarted($this->event1, $now)], $subscription->events());

        $subscription->commit();

        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertSame(1, $subscription->version());
        self::assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        self::assertEquals([], $subscription->events());

        $this->stream1 = new InMemoryStream($this->event1, $this->event2);
        $this->stream2 = new InMemoryStream($this->event3, $this->event4);
        $this->stream3 = new InMemoryStream($this->event5);

        $this->store
            ->expects(self::exactly(3))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream1,
                $this->stream2,
                $this->stream3
            )
        ;

        $this->listener3
            ->expects(self::exactly(5))
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
            ->expects(self::exactly(5))
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

        self::assertEquals([$this->event1, $this->event2], $events);
        self::assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionIgnoredEvent($this->event2, $now)], $subscription->events());
        self::assertSame(1, $subscription->version());

        $subscription->commit();

        self::assertEquals([], $subscription->events());
        self::assertSame(3, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event3, $this->event4], $events);
        self::assertEquals([new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now)], $subscription->events());
        self::assertSame(3, $subscription->version());

        $subscription->commit();

        self::assertEquals([], $subscription->events());
        self::assertSame(5, $subscription->version());

        $events = $subscription->subscribeTo($this->store, \PHP_INT_MAX); // subscription will stop listening to #events right after it being completed, even if $limit was not exhausted.
        $events = iterator_to_array($events);

        self::assertEquals([$this->event5], $events);
        self::assertEquals([new SubscriptionListenedToEvent($this->event5, $now), new SubscriptionCompleted($now)], $subscription->events());
        self::assertSame(5, $subscription->version());

        $subscription->commit();

        self::assertEquals([], $subscription->events());
        self::assertSame(7, $subscription->version());
    }

    public function testReplayingListener(): void
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener1
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $this->listener1
            ->expects(self::never())
            ->method('replay')
        ;

        $subscription = new Subscription($this->listener1, $this->clock);

        self::assertSame($subscription->listener(), $this->listener1);
        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertNull($subscription->lastEvent());
        self::assertEmpty($subscription->events());
        self::assertSame(0, $subscription->version());

        $event0 = new SubscriptionStarted($this->event1, $now);
        $event0 = Event\Envelope::new($event0, $this->id1, 1);
        $event1 = new SubscriptionListenedToEvent($this->event1, $now);
        $event1 = Event\Envelope::new($event1, $this->id1, 2);
        $event2 = new SubscriptionListenedToEvent($this->event2, $now);
        $event2 = Event\Envelope::new($event2, $this->id1, 3);

        $this->stream1 = new InMemoryStream($event0, $event1, $event2);

        $subscription->replay($this->stream1);

        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertSame($event2, $subscription->lastReplayed());
        self::assertSame(3, $subscription->version());
        self::assertEquals($event2, $subscription->lastEvent());
        self::assertEmpty($subscription->events());

        $this->stream2 = new InMemoryStream($this->event3, $this->event4);
        $this->stream3 = new InMemoryStream($this->event5);

        $this->store
            ->expects(self::exactly(2))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream2,
                $this->stream3
            )
        ;

        $this->listener1
            ->expects(self::exactly(3))
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

        self::assertEquals([$this->event3, $this->event4], $events);
        self::assertEquals([new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now)], $subscription->events());
        self::assertSame(3, $subscription->version());

        $subscription->commit();

        self::assertEquals([], $subscription->events());
        self::assertSame(5, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event5], $events);
        self::assertEquals([new SubscriptionListenedToEvent($this->event5, $now)], $subscription->events());
        self::assertSame(5, $subscription->version());

        $subscription->commit();

        self::assertEquals([], $subscription->events());
        self::assertSame(6, $subscription->version());
    }

    public function testReplayingStatefulListenerWithExistingState(): void
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener9
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->with()
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener9, $this->clock);

        self::assertSame($subscription->listener(), $this->listener9);
        self::assertNull($subscription->lastReplayed());
        self::assertNull($subscription->lastEvent());
        self::assertEmpty($subscription->events());
        self::assertSame(0, $subscription->version());

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
            ->expects(self::exactly(3))
            ->method('fromState')
            ->withConsecutive(
                [self::callback(fn (Listener\State $state) => $state->equals($this->state1))],
                [self::callback(fn (Listener\State $state) => $state->equals($this->state2))],
                [self::callback(fn (Listener\State $state) => $state->equals($this->state3))]
            )
        ;

        $subscription->replay($this->stream1);

        self::assertSame($event3, $subscription->lastReplayed());
        self::assertSame(4, $subscription->version());
        self::assertEquals($event3, $subscription->lastEvent());
        self::assertEmpty($subscription->events());

        $this->stream2 = new InMemoryStream($this->event3, $this->event4);
        $this->stream3 = new InMemoryStream($this->event5);

        $this->store
            ->expects(self::exactly(2))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream2,
                $this->stream3
            )
        ;

        $this->listener9
            ->expects(self::exactly(3))
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

        self::assertEquals([$this->event3, $this->event4], $events);
        self::assertEquals([new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now), new SubscriptionListenersStateChanged($this->state2, $now)], $subscription->events());
        self::assertSame(4, $subscription->version());

        $subscription->commit();

        self::assertEquals([], $subscription->events());
        self::assertSame(7, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event5], $events);
        self::assertEquals([new SubscriptionListenedToEvent($this->event5, $now), new SubscriptionListenersStateChanged($this->state3, $now)], $subscription->events());
        self::assertSame(7, $subscription->version());

        $subscription->commit();

        self::assertEquals([], $subscription->events());
        self::assertSame(9, $subscription->version());
    }

    public function testReplayingStatefulListenerWithoutExistingState(): void
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener9
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->with()
            ->willReturn($this->id1)
        ;
        $subscription = new Subscription($this->listener9, $this->clock);

        self::assertSame($subscription->listener(), $this->listener9);
        self::assertNull($subscription->lastReplayed());
        self::assertNull($subscription->lastEvent());
        self::assertEmpty($subscription->events());
        self::assertSame(0, $subscription->version());

        $event0 = new SubscriptionStarted($this->event1, $now);
        $event0 = Event\Envelope::new($event0, $this->id1, 1);
        $event1 = new SubscriptionListenedToEvent($this->event1, $now);
        $event1 = Event\Envelope::new($event1, $this->id1, 2);
        $event2 = new SubscriptionListenedToEvent($this->event2, $now);
        $event2 = Event\Envelope::new($event2, $this->id1, 3);

        $this->stream1 = new InMemoryStream($event0, $event1, $event2);

        $subscription->replay($this->stream1);

        self::assertSame($event2, $subscription->lastReplayed());
        self::assertSame(3, $subscription->version());
        self::assertEquals($event2, $subscription->lastEvent());
        self::assertEmpty($subscription->events());

        $this->stream2 = new InMemoryStream($this->event3, $this->event4);
        $this->stream3 = new InMemoryStream($this->event5);

        $this->store
            ->expects(self::exactly(2))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream2,
                $this->stream3
            )
        ;
        $this->listener9
            ->expects(self::exactly(3))
            ->method('fromState')
            ->withConsecutive(
                [self::callback(fn (Listener\State $state) => $state->equals($this->state1))],
                [self::callback(fn (Listener\State $state) => $state->equals($this->state2))],
                [self::callback(fn (Listener\State $state) => $state->equals($this->state3))]
            )
        ;
        $this->listener9
            ->expects(self::exactly(3))
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

        self::assertEquals([$this->event3, $this->event4], $events);
        self::assertEquals([new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenersStateChanged($this->state1, $now), new SubscriptionListenedToEvent($this->event4, $now), new SubscriptionListenersStateChanged($this->state2, $now)], $subscription->events());
        self::assertSame(3, $subscription->version());

        $subscription->commit();

        self::assertEquals([], $subscription->events());
        self::assertSame(7, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event5], $events);
        self::assertEquals([new SubscriptionListenedToEvent($this->event5, $now), new SubscriptionListenersStateChanged($this->state3, $now)], $subscription->events());
        self::assertSame(7, $subscription->version());

        $subscription->commit();

        self::assertEquals([], $subscription->events());
        self::assertSame(9, $subscription->version());
    }

    public function testReplayingListenerWithAnEmptyStream(): void
    {
        $this->listener1
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $this->listener1
            ->expects(self::never())
            ->method('replay')
        ;

        $subscription = new Subscription($this->listener1, $this->clock);

        self::assertSame($subscription->listener(), $this->listener1);
        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertNull($subscription->lastEvent());
        self::assertEmpty($subscription->events());
        self::assertSame(0, $subscription->version());

        $this->stream1 = new InMemoryStream();

        $subscription->replay($this->stream1);

        self::assertSame($subscription->listener(), $this->listener1);
        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertNull($subscription->lastEvent());
        self::assertEmpty($subscription->events());
        self::assertSame(0, $subscription->version());
    }

    public function testCompletingListener(): void
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener3
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->with()
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener3, $this->clock);

        self::assertSame($subscription->listener(), $this->listener3);
        self::assertFalse($subscription->completed());
        self::assertNull($subscription->lastReplayed());
        self::assertNull($subscription->lastEvent());
        self::assertEmpty($subscription->events());
        self::assertSame(0, $subscription->version());
        self::assertFalse($subscription->paused());

        $event0 = new SubscriptionStarted($this->event1, $now);
        $event0 = Event\Envelope::new($event0, $this->id1, 1);

        $this->stream1 = new InMemoryStream($event0);

        $subscription->replay($this->stream1);

        self::assertSame($event0, $subscription->lastReplayed());
        self::assertSame($event0, $subscription->lastEvent());
        self::assertEmpty($subscription->events());
        self::assertSame(1, $subscription->version());
        self::assertFalse($subscription->completed());
        self::assertFalse($subscription->paused());

        // mind you that $this->event3 won't be listened to, because $this->event2 completes subscription
        $this->stream2 = new InMemoryStream($this->event1, $this->event2, $this->event3);

        $this->store
            ->expects(self::once())
            ->method('stream')
            ->willReturn($this->stream2)
        ;

        $this->listener3
            ->expects(self::exactly(2))
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
            ->expects(self::exactly(2))
            ->method('completed')
            ->willReturnOnConsecutiveCalls(
                false,
                true
            )
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event1, $this->event2], $events);
        self::assertTrue($subscription->completed());
        self::assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionListenedToEvent($this->event2, $now), new SubscriptionCompleted($now)], $subscription->events());
        self::assertSame($event0, $subscription->lastReplayed());
        self::assertEquals(new SubscriptionCompleted($now), $subscription->lastEvent());
        self::assertSame(1, $subscription->version());
        self::assertFalse($subscription->paused());

        $subscription->commit();

        self::assertTrue($subscription->completed());
        self::assertEmpty($subscription->events());
        self::assertSame($event0, $subscription->lastReplayed());
        self::assertEquals(new SubscriptionCompleted($now), $subscription->lastEvent());
        self::assertSame(4, $subscription->version());
        self::assertFalse($subscription->paused());

        $subscription->pause();

        self::assertTrue($subscription->completed());
        self::assertEmpty($subscription->events());
        self::assertSame($event0, $subscription->lastReplayed());
        self::assertEquals(new SubscriptionCompleted($now), $subscription->lastEvent());
        self::assertSame(4, $subscription->version());
        self::assertFalse($subscription->paused());

        $subscription->commit();

        self::assertTrue($subscription->completed());
        self::assertEmpty($subscription->events());
        self::assertSame($event0, $subscription->lastReplayed());
        self::assertEquals(new SubscriptionCompleted($now), $subscription->lastEvent());
        self::assertSame(4, $subscription->version());
        self::assertFalse($subscription->paused());

        $subscription->unpause();

        self::assertTrue($subscription->completed());
        self::assertEmpty($subscription->events());
        self::assertSame($event0, $subscription->lastReplayed());
        self::assertEquals(new SubscriptionCompleted($now), $subscription->lastEvent());
        self::assertSame(4, $subscription->version());
        self::assertFalse($subscription->paused());

        $subscription->commit();

        self::assertTrue($subscription->completed());
        self::assertEmpty($subscription->events());
        self::assertSame($event0, $subscription->lastReplayed());
        self::assertEquals(new SubscriptionCompleted($now), $subscription->lastEvent());
        self::assertSame(4, $subscription->version());
        self::assertFalse($subscription->paused());

        $this->expectExceptionObject(new Event\Subscription\Exception\SubscriptionAlreadyCompleted($subscription));

        $events = $subscription->subscribeTo($this->store);

        iterator_to_array($events);
    }

    public function testStartingAlreadyStartedSubscription(): void
    {
        $this->listener1
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener1, $this->clock);

        self::assertSame($subscription->listener(), $this->listener1);
        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertNull($subscription->lastEvent());
        self::assertEmpty($subscription->events());

        $subscription->startFor($this->event1);

        $this->expectExceptionObject(new Event\Subscription\Exception\SubscriptionAlreadyStarted($subscription));

        $subscription->startFor($this->event2);
    }

    public function testSubscribingAlreadyCompletedListener(): void
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener3
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->with()
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener3, $this->clock);

        self::assertSame($subscription->listener(), $this->listener3);
        self::assertFalse($subscription->completed());

        $event0 = new SubscriptionStarted($this->event1, $now);
        $event0 = Event\Envelope::new($event0, $this->id1, 1);
        $event1 = new SubscriptionListenedToEvent($this->event2, $now);
        $event1 = Event\Envelope::new($event1, $this->id1, 2);
        $event2 = new SubscriptionCompleted($now);
        $event2 = Event\Envelope::new($event2, $this->id1, 3);

        $this->stream1 = new InMemoryStream($event0, $event1, $event2);

        $subscription->replay($this->stream1);

        self::assertTrue($subscription->completed());
        self::assertSame($event2, $subscription->lastReplayed());
        self::assertSame($event2, $subscription->lastEvent());
        self::assertEmpty($subscription->events());
        self::assertSame(3, $subscription->version());

        $this->store
            ->expects(self::never())
            ->method('stream')
        ;

        $this->expectExceptionObject(new Event\Subscription\Exception\SubscriptionAlreadyCompleted($subscription));

        $events = $subscription->subscribeTo($this->store);

        iterator_to_array($events);
    }

    public function testSubscribingRestartedAndCompletedListener(): void
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener3
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->with()
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener3, $this->clock);

        self::assertSame($subscription->listener(), $this->listener3);
        self::assertFalse($subscription->completed());

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

        self::assertTrue($subscription->completed());
        self::assertSame($event5, $subscription->lastReplayed());
        self::assertSame($event5, $subscription->lastEvent());
        self::assertEmpty($subscription->events());
        self::assertSame(6, $subscription->version());

        $this->store
            ->expects(self::never())
            ->method('stream')
        ;

        $this->expectExceptionObject(new Event\Subscription\Exception\SubscriptionAlreadyCompleted($subscription));

        $events = $subscription->subscribeTo($this->store);

        iterator_to_array($events);
    }

    public function testNotStartedListener(): void
    {
        $this->listener1
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener1, $this->clock);

        self::assertSame($subscription->listener(), $this->listener1);
        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertNull($subscription->lastEvent());
        self::assertEmpty($subscription->events());

        $this->store
            ->expects(self::never())
            ->method('stream')
        ;

        $this->listener1
            ->expects(self::never())
            ->method('on')
        ;

        $this->expectExceptionObject(new Event\Subscription\Exception\SubscriptionNotStartedYet($subscription));

        $events = $subscription->subscribeTo($this->store);

        iterator_to_array($events);
    }

    public function testRestartingSubscriptionForResettableButNonReplayableListener(): void
    {
        $this->listener4
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->with()
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener4, $this->clock);

        self::assertSame($subscription->listener(), $this->listener4);
        self::assertNull($subscription->lastReplayed());
        self::assertNull($subscription->lastEvent());
        self::assertEmpty($subscription->events());
        self::assertSame(0, $subscription->version());

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

        self::assertSame($event3, $subscription->lastReplayed());
        self::assertSame($event3, $subscription->lastEvent());
        self::assertSame([], $subscription->events());
        self::assertSame(4, $subscription->version());

        $subscription->restart();

        self::assertSame($event3, $subscription->lastReplayed());
        self::assertEquals(new SubscriptionRestarted($this->event1, $now), $subscription->lastEvent());
        self::assertEquals([new SubscriptionRestarted($this->event1, $now)], $subscription->events());
        self::assertSame(4, $subscription->version());

        $subscription->commit();

        self::assertSame($event3, $subscription->lastReplayed());
        self::assertEquals(new SubscriptionRestarted($this->event1, $now), $subscription->lastEvent());
        self::assertSame([], $subscription->events());
        self::assertSame(5, $subscription->version());

        $subscription->restart();

        // nothing changed as consecutive restarts are ignored
        self::assertSame($event3, $subscription->lastReplayed());
        self::assertEquals(new SubscriptionRestarted($this->event1, $now), $subscription->lastEvent());
        self::assertSame([], $subscription->events());
        self::assertSame(5, $subscription->version());

        $this->stream2 = new InMemoryStream($this->event1, $this->event3, $this->event4, $this->event5); // lets say that after restart listener do not need to listen to $this->>event2

        $this->store
            ->expects(self::once())
            ->method('stream')
            ->willReturn($this->stream2)
        ;

        $this->listener4
            ->expects(self::once())
            ->method('reset')
        ;
        $this->listener4
            ->expects(self::exactly(4))
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

        self::assertSame([$this->event1, $this->event3, $this->event4, $this->event5], $events);
        self::assertSame($event3, $subscription->lastReplayed());
        self::assertEquals(new SubscriptionListenedToEvent($this->event5, $now), $subscription->lastEvent());
        self::assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionListenedToEvent($this->event3, $now), new SubscriptionIgnoredEvent($this->event4, $now), new SubscriptionListenedToEvent($this->event5, $now)], $subscription->events());
        self::assertSame(5, $subscription->version());

        $subscription->commit();

        self::assertSame($event3, $subscription->lastReplayed());
        self::assertEquals(new SubscriptionListenedToEvent($this->event5, $now), $subscription->lastEvent());
        self::assertSame([], $subscription->events());
        self::assertSame(9, $subscription->version());
    }

    public function testRestartingNotStartedSubscription(): void
    {
        $this->listener1
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener1, $this->clock);

        self::assertSame($subscription->listener(), $this->listener1);
        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertNull($subscription->lastEvent());
        self::assertEmpty($subscription->events());

        $this->expectExceptionObject(new Event\Subscription\Exception\SubscriptionNotStartedYet($subscription));

        $subscription->restart();
    }

    public function testRestartingCompletedSubscription(): void
    {
        $this->listener6
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->with()
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener6, $this->clock);

        self::assertSame($subscription->listener(), $this->listener6);
        self::assertNull($subscription->lastReplayed());
        self::assertNull($subscription->lastEvent());
        self::assertEmpty($subscription->events());
        self::assertSame(0, $subscription->version());
        self::assertFalse($subscription->completed());

        $this->listener6
            ->expects(self::exactly(4))
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

        self::assertSame($event3, $subscription->lastReplayed());
        self::assertSame($event3, $subscription->lastEvent());
        self::assertSame([], $subscription->events());
        self::assertSame(4, $subscription->version());
        self::assertTrue($subscription->completed());

        $subscription->restart();

        self::assertSame($event3, $subscription->lastReplayed());
        self::assertEquals(new SubscriptionRestarted($this->event2, $now), $subscription->lastEvent());
        self::assertEquals([new SubscriptionRestarted($this->event2, $now)], $subscription->events());
        self::assertSame(4, $subscription->version());
        self::assertFalse($subscription->completed());

        $subscription->commit();

        self::assertSame($event3, $subscription->lastReplayed());
        self::assertEquals(new SubscriptionRestarted($this->event2, $now), $subscription->lastEvent());
        self::assertSame([], $subscription->events());
        self::assertSame(5, $subscription->version());
        self::assertFalse($subscription->completed());

        $this->stream3 = new InMemoryStream($this->event2, $this->event3, $this->event4, $this->event5);

        $this->store
            ->expects(self::once())
            ->method('stream')
            ->willReturnReference($this->stream3)
        ;

        $this->listener6
            ->expects(self::once())
            ->method('reset')
        ;

        $this->listener6
            ->expects(self::exactly(4))
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

        self::assertSame([$this->event2, $this->event3, $this->event4, $this->event5], $events);
        self::assertSame($event3, $subscription->lastReplayed());
        self::assertEquals(new SubscriptionListenedToEvent($this->event5, $now), $subscription->lastEvent());
        self::assertEquals([new SubscriptionListenedToEvent($this->event2, $now), new SubscriptionListenedToEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now), new SubscriptionListenedToEvent($this->event5, $now)], $subscription->events());
        self::assertSame(5, $subscription->version());
        self::assertFalse($subscription->completed());

        $subscription->commit();

        self::assertSame($event3, $subscription->lastReplayed());
        self::assertEquals(new SubscriptionListenedToEvent($this->event5, $now), $subscription->lastEvent());
        self::assertSame([], $subscription->events());
        self::assertSame(9, $subscription->version());
        self::assertFalse($subscription->completed());
    }

    public function testRestartingNonResettableListener(): void
    {
        $this->listener1
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->with()
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener1, $this->clock);

        self::assertSame($subscription->listener(), $this->listener1);
        self::assertFalse($subscription->completed());
        self::assertNull($subscription->lastReplayed());
        self::assertNull($subscription->lastEvent());
        self::assertEmpty($subscription->events());
        self::assertSame(0, $subscription->version());
        self::assertFalse($subscription->completed());

        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');
        $event0 = new SubscriptionStarted($this->event1, $now);
        $event0 = Event\Envelope::new($event0, $this->id1, 1);
        $event1 = new SubscriptionListenedToEvent($this->event2, $now);
        $event1 = Event\Envelope::new($event1, $this->id1, 2);
        $event2 = new SubscriptionListenedToEvent($this->event3, $now);
        $event2 = Event\Envelope::new($event2, $this->id1, 3);

        $this->stream1 = new InMemoryStream($event0, $event1, $event2);

        $subscription->replay($this->stream1);

        self::assertSame($event2, $subscription->lastReplayed());
        self::assertSame($event2, $subscription->lastEvent());
        self::assertEmpty($subscription->events());
        self::assertSame(3, $subscription->version());
        self::assertFalse($subscription->completed());

        $this->listener1
            ->expects(self::never())
            ->method('reset')
        ;

        $this->expectExceptionObject(new Event\Subscription\Exception\SubscriptionRestartNotPossible($subscription));

        $subscription->restart();
    }

    public function testEquals(): void
    {
        $id1 = $this->createMock(Listener\Id::class);
        $id2 = $this->createMock(Listener\Id::class);

        $subscription1 = new Subscription($this->listener1, $this->clock);

        self::assertFalse($subscription1->equals(new \stdClass()));

        $subscription2 = new Subscription($this->listener3, $this->clock);

        $this->listener1
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->willReturn($id1)
        ;

        $this->listener3
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->willReturn($id2)
        ;

        $id1
            ->expects(self::atLeastOnce())
            ->method('equals')
            ->with($id2)
            ->willReturn(true)
        ;

        self::assertTrue($subscription1->equals($subscription2));

        $id2
            ->expects(self::atLeastOnce())
            ->method('equals')
            ->with($id1)
            ->willReturn(false)
        ;

        self::assertFalse($subscription2->equals($subscription1));
    }

    public function testStartingSubscriptionWithResettableListenerWithFirstEventIgnored(): void
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener4
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $subscription = new Subscription($this->listener4, $this->clock);

        self::assertSame($subscription->listener(), $this->listener4);
        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertNull($subscription->lastEvent());
        self::assertEmpty($subscription->events());
        self::assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertSame(0, $subscription->version());
        self::assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        self::assertEquals([new SubscriptionStarted($this->event1, $now)], $subscription->events());

        $subscription->commit();

        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertSame(1, $subscription->version());
        self::assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        self::assertEquals([], $subscription->events());

        $this->stream1 = new InMemoryStream($this->event1);

        $this->store
            ->expects(self::once())
            ->method('stream')
            ->willReturn($this->stream1)
        ;

        $this->listener4
            ->expects(self::once())
            ->method('on')
            ->with($this->event1)
            ->willReturn(false)
        ;

        $this->listener4
            ->expects(self::once())
            ->method('reset')
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event1], $events);
        self::assertEquals([new SubscriptionIgnoredEvent($this->event1, $now)], $subscription->events());
        self::assertSame(1, $subscription->version());

        $subscription->commit();

        self::assertEmpty($subscription->events());
        self::assertSame(2, $subscription->version());
    }

    public function testStartingSubscriptionWithResettableListenerWithFirstEventNotIgnored(): void
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener4
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $subscription = new Subscription($this->listener4, $this->clock);

        self::assertSame($subscription->listener(), $this->listener4);
        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertNull($subscription->lastEvent());
        self::assertEmpty($subscription->events());
        self::assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertSame(0, $subscription->version());
        self::assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        self::assertEquals([new SubscriptionStarted($this->event1, $now)], $subscription->events());

        $subscription->commit();

        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertSame(1, $subscription->version());
        self::assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        self::assertEquals([], $subscription->events());

        $this->stream1 = new InMemoryStream($this->event1);

        $this->store
            ->expects(self::once())
            ->method('stream')
            ->willReturn($this->stream1)
        ;

        $this->listener4
            ->expects(self::once())
            ->method('on')
            ->with($this->event1)
            ->willReturn(true)
        ;

        $this->listener4
            ->expects(self::once())
            ->method('reset')
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event1], $events);
        self::assertEquals([new SubscriptionListenedToEvent($this->event1, $now)], $subscription->events());
        self::assertSame(1, $subscription->version());

        $subscription->commit();

        self::assertEmpty($subscription->events());
        self::assertSame(2, $subscription->version());
    }

    public function testRestartingFreshlyStartedSubscription(): void
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener4
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $subscription = new Subscription($this->listener4, $this->clock);

        self::assertSame($subscription->listener(), $this->listener4);
        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertNull($subscription->lastEvent());
        self::assertEmpty($subscription->events());
        self::assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertSame(0, $subscription->version());
        self::assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        self::assertEquals([new SubscriptionStarted($this->event1, $now)], $subscription->events());

        $subscription->commit();

        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertSame(1, $subscription->version());
        self::assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        self::assertEquals([], $subscription->events());

        $subscription->restart();

        // nothing changed as consecutive restarts are ignored
        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertSame(1, $subscription->version());
        self::assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        self::assertEquals([], $subscription->events());
    }

    public function testNonPositiveLimitGivenWhileSubscribingToTheEventStoreBeforeStarting(): void
    {
        $subscription = new Subscription($this->listener1, $this->clock);

        $this->store
            ->expects(self::never())
            ->method('stream')
        ;

        $this->listener1
            ->expects(self::never())
            ->method('on')
        ;
        $this->listener3
            ->expects(self::never())
            ->method('completed')
        ;

        $this->expectExceptionObject(new \InvalidArgumentException('$limit must be a positive integer, but 0 was given.'));

        $events = $subscription->subscribeTo($this->store, 0);
        $events->rewind();
    }

    public function testNonPositiveLimitGivenWhileSubscribingToTheEventStoreAfterStarting(): void
    {
        $this->listener1
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->with()
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener1, $this->clock);
        $subscription->startFor($this->event1);
        $subscription->commit();

        $this->store
            ->expects(self::never())
            ->method('stream')
        ;

        $this->listener1
            ->expects(self::never())
            ->method('on')
        ;
        $this->listener3
            ->expects(self::never())
            ->method('completed')
        ;

        $this->expectExceptionObject(new \InvalidArgumentException('$limit must be a positive integer, but -1 was given.'));

        $events = $subscription->subscribeTo($this->store, -1);
        $events->rewind();
    }

    public function testContinuousListeningWithNumberOfEventsBeingExactlyImposedLimit(): void
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener1
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $this->listener1
            ->expects(self::never())
            ->method('replay')
        ;

        $subscription = new Subscription($this->listener1, $this->clock);

        self::assertSame($subscription->listener(), $this->listener1);
        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertNull($subscription->lastEvent());
        self::assertEmpty($subscription->events());
        self::assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertSame(0, $subscription->version());
        self::assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        self::assertEquals([new SubscriptionStarted($this->event1, $now)], $subscription->events());

        $subscription->commit();

        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertSame(1, $subscription->version());
        self::assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        self::assertEquals([], $subscription->events());

        $this->stream1 = new InMemoryStream($this->event1, $this->event2);
        $this->stream2 = new InMemoryStream($this->event3, $this->event4);
        $this->stream3 = new InMemoryStream($this->event5);

        $this->store
            ->expects(self::exactly(3))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream1,
                $this->stream2,
                $this->stream3
            )
        ;

        $this->listener1
            ->expects(self::exactly(5))
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
            ->expects(self::never())
            ->method('completed')
        ;

        $events = $subscription->subscribeTo($this->store, 5);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event1, $this->event2, $this->event3, $this->event4, $this->event5], $events);
        self::assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionIgnoredEvent($this->event2, $now), new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now), new SubscriptionListenedToEvent($this->event5, $now)], $subscription->events());
        self::assertSame(1, $subscription->version());

        $subscription->commit();

        self::assertEquals([], $subscription->events());
        self::assertSame(6, $subscription->version());
    }

    public function testContinuousListeningWithNumberOfEventsBeingMoreThanImposedLimit1(): void
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener1
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $this->listener1
            ->expects(self::never())
            ->method('replay')
        ;

        $subscription = new Subscription($this->listener1, $this->clock);

        self::assertSame($subscription->listener(), $this->listener1);
        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertNull($subscription->lastEvent());
        self::assertEmpty($subscription->events());
        self::assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertSame(0, $subscription->version());
        self::assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        self::assertEquals([new SubscriptionStarted($this->event1, $now)], $subscription->events());

        $subscription->commit();

        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertSame(1, $subscription->version());
        self::assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        self::assertEquals([], $subscription->events());

        $this->stream1 = new InMemoryStream($this->event1, $this->event2);
        $this->stream2 = new InMemoryStream($this->event3, $this->event4, $this->event5);

        $this->store
            ->expects(self::exactly(2))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream1,
                $this->stream2
            )
        ;

        $this->listener1
            ->expects(self::exactly(4))
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
            ->expects(self::never())
            ->method('completed')
        ;

        $events = $subscription->subscribeTo($this->store, 4);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event1, $this->event2, $this->event3, $this->event4], $events);
        self::assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionIgnoredEvent($this->event2, $now), new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now)], $subscription->events());
        self::assertSame(1, $subscription->version());

        $subscription->commit();

        self::assertEquals([], $subscription->events());
        self::assertSame(5, $subscription->version());
    }

    public function testContinuousListeningWithNumberOfEventsBeingMoreThanImposedLimit2(): void
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener1
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $this->listener1
            ->expects(self::never())
            ->method('replay')
        ;

        $subscription = new Subscription($this->listener1, $this->clock);

        self::assertSame($subscription->listener(), $this->listener1);
        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertNull($subscription->lastEvent());
        self::assertEmpty($subscription->events());
        self::assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertSame(0, $subscription->version());
        self::assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        self::assertEquals([new SubscriptionStarted($this->event1, $now)], $subscription->events());

        $subscription->commit();

        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertSame(1, $subscription->version());
        self::assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        self::assertEquals([], $subscription->events());

        $this->stream1 = new InMemoryStream($this->event1, $this->event2);
        $this->stream2 = new InMemoryStream($this->event3, $this->event4);

        $this->store
            ->expects(self::exactly(2))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream1,
                $this->stream2
            )
        ;

        $this->listener1
            ->expects(self::exactly(4))
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
            ->expects(self::never())
            ->method('completed')
        ;

        $events = $subscription->subscribeTo($this->store, 4);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event1, $this->event2, $this->event3, $this->event4], $events);
        self::assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionIgnoredEvent($this->event2, $now), new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now)], $subscription->events());
        self::assertSame(1, $subscription->version());

        $subscription->commit();

        self::assertEquals([], $subscription->events());
        self::assertSame(5, $subscription->version());
    }

    public function testContinuousListeningWithNumberOfEventsBeingLessThanImposedLimit1(): void
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener1
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $this->listener1
            ->expects(self::never())
            ->method('replay')
        ;

        $subscription = new Subscription($this->listener1, $this->clock);

        self::assertSame($subscription->listener(), $this->listener1);
        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertNull($subscription->lastEvent());
        self::assertEmpty($subscription->events());
        self::assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertSame(0, $subscription->version());
        self::assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        self::assertEquals([new SubscriptionStarted($this->event1, $now)], $subscription->events());

        $subscription->commit();

        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertSame(1, $subscription->version());
        self::assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        self::assertEquals([], $subscription->events());

        $this->stream1 = new InMemoryStream($this->event1, $this->event2);
        $this->stream2 = new InMemoryStream($this->event3, $this->event4, $this->event5);
        $this->stream3 = new InMemoryStream();

        $this->store
            ->expects(self::exactly(3))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream1,
                $this->stream2,
                $this->stream3
            )
        ;

        $this->listener1
            ->expects(self::exactly(5))
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
            ->expects(self::never())
            ->method('completed')
        ;

        $events = $subscription->subscribeTo($this->store, 6);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event1, $this->event2, $this->event3, $this->event4, $this->event5], $events);
        self::assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionIgnoredEvent($this->event2, $now), new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now), new SubscriptionListenedToEvent($this->event5, $now)], $subscription->events());
        self::assertSame(1, $subscription->version());

        $subscription->commit();

        self::assertEquals([], $subscription->events());
        self::assertSame(6, $subscription->version());
    }

    public function testCompletingListenerWhileContinuousListening(): void
    {
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->listener3
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener3, $this->clock);

        self::assertSame($subscription->listener(), $this->listener3);
        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertNull($subscription->lastEvent());
        self::assertEmpty($subscription->events());
        self::assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertSame(0, $subscription->version());
        self::assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        self::assertEquals([new SubscriptionStarted($this->event1, $now)], $subscription->events());

        $subscription->commit();

        self::assertSame($this->id1, $subscription->subscriptionId());
        self::assertSame($this->id1, $subscription->producerId());
        self::assertNull($subscription->lastReplayed());
        self::assertSame(1, $subscription->version());
        self::assertEquals(new SubscriptionStarted($this->event1, $now), $subscription->lastEvent());
        self::assertEquals([], $subscription->events());

        $this->stream1 = new InMemoryStream($this->event1, $this->event2);
        $this->stream2 = new InMemoryStream($this->event3, $this->event4);
        $this->stream3 = new InMemoryStream($this->event5);

        $this->store
            ->expects(self::exactly(3))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream1,
                $this->stream2,
                $this->stream3
            )
        ;

        $this->listener3
            ->expects(self::exactly(5))
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
            ->expects(self::exactly(5))
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

        self::assertEquals([$this->event1, $this->event2, $this->event3, $this->event4, $this->event5], $events);
        self::assertEquals([new SubscriptionListenedToEvent($this->event1, $now), new SubscriptionIgnoredEvent($this->event2, $now), new SubscriptionIgnoredEvent($this->event3, $now), new SubscriptionListenedToEvent($this->event4, $now), new SubscriptionListenedToEvent($this->event5, $now), new SubscriptionCompleted($now)], $subscription->events());
        self::assertSame(1, $subscription->version());

        $subscription->commit();

        self::assertEquals([], $subscription->events());
        self::assertSame(7, $subscription->version());
    }
}

namespace Streak\Infrastructure\Domain\Event\Sourced\SubscriptionTest;

use Streak\Domain\Event;
use Streak\Domain\Event\Listener;

abstract class CompletableListener implements Listener, Listener\Completable
{
}

abstract class ResettableListener implements Listener, Listener\Resettable
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

abstract class StatefulListener implements Listener, Listener\Stateful
{
}

abstract class IterableStream implements Event\Stream, \IteratorAggregate
{
}
