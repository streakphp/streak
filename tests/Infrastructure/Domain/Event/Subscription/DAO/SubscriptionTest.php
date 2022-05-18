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

namespace Streak\Infrastructure\Domain\Event\Subscription\DAO;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Clock;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Domain\EventStore;
use Streak\Domain\Id\UUID;
use Streak\Infrastructure\Domain\Clock\FixedClock;
use Streak\Infrastructure\Domain\Event\InMemoryStream;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\InMemoryState;
use Streak\Infrastructure\Domain\Event\Subscription\DAO\SubscriptionTest\CompletableListener;
use Streak\Infrastructure\Domain\Event\Subscription\DAO\SubscriptionTest\FilteringListener;
use Streak\Infrastructure\Domain\Event\Subscription\DAO\SubscriptionTest\IterableStream;
use Streak\Infrastructure\Domain\Event\Subscription\DAO\SubscriptionTest\ResettableListener;
use Streak\Infrastructure\Domain\Event\Subscription\DAO\SubscriptionTest\ResettableListenerThatCanPickStartingEvent;
use Streak\Infrastructure\Domain\Event\Subscription\DAO\SubscriptionTest\StatefulListener;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\Event\Subscription\DAO\Subscription
 */
class SubscriptionTest extends TestCase
{
    private Listener|MockObject $listener1;
    private CompletableListener|MockObject $listener3;
    private ResettableListener|MockObject $listener4;
    private ResettableListenerThatCanPickStartingEvent|MockObject $listener7;
    private FilteringListener|MockObject $listener8;
    private StatefulListener|MockObject $listener9;

    private Listener\Id $id1;

    private EventStore|MockObject $store;

    private Event\Stream|MockObject $stream1;
    private Event\Stream|MockObject $stream2;
    private Event\Stream|MockObject $stream3;

    private Event\Envelope $event1;
    private Event\Envelope $event2;
    private Event\Envelope $event3;
    private Event\Envelope $event4;
    private Event\Envelope $event5;

    private Clock|MockObject $clock;

    protected function setUp(): void
    {
        $this->listener1 = $this->getMockBuilder(Listener::class)->addMethods(['replay', 'reset', 'completed'])->getMockForAbstractClass();
        $this->listener3 = $this->getMockBuilder(CompletableListener::class)->getMock();
        $this->listener4 = $this->getMockBuilder(ResettableListener::class)->getMock();
        $this->listener7 = $this->getMockBuilder(ResettableListenerThatCanPickStartingEvent::class)->getMock();
        $this->listener8 = $this->getMockBuilder(FilteringListener::class)->getMock();
        $this->listener9 = $this->getMockBuilder(StatefulListener::class)->getMock();

        $this->id1 = new class ('f5e65690-e50d-4312-a175-b004ec1bd42a') extends UUID implements Listener\Id {
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

        $this->clock = new FixedClock(new \DateTime('2018-09-28 19:12:32.763188 +00:00'));
    }

    public function testListener(): void
    {
        $this->listener1
            ->expects(self::atLeastOnce())
            ->method('id')
            ->willReturn($this->id1)
        ;
        $this->listener1
            ->expects(self::never())
            ->method('replay')
        ;

        $subscription = new Subscription($this->listener1, $this->clock);

        self::assertSame($subscription->listener(), $this->listener1);
        self::assertSame($this->id1, $subscription->id());
        self::assertSame(0, $subscription->version());
        self::assertFalse($subscription->starting());
        self::assertFalse($subscription->paused());

        $subscription->pause();
        $subscription->unpause();

        $subscription->startFor($this->event1);

        self::assertSame($this->id1, $subscription->id());
        self::assertSame(1, $subscription->version());
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
        self::assertSame(3, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event3, $this->event4], $events);
        self::assertSame(5, $subscription->version());
        self::assertFalse($subscription->paused());

        $subscription->pause();

        self::assertTrue($subscription->paused());

        $subscription->pause();

        self::assertTrue($subscription->paused());

        try {
            $events = $subscription->subscribeTo($this->store);
            iterator_to_array($events);
            self::fail();
        } catch (Event\Subscription\Exception\SubscriptionPaused $exception) {
            self::assertSame($subscription, $exception->subscription());
        }

        $subscription->unpause();

        self::assertFalse($subscription->paused());

        $subscription->unpause();

        self::assertFalse($subscription->paused());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event5], $events);
        self::assertSame(6, $subscription->version());
        self::assertFalse($subscription->paused());
    }

    public function testStatefulListener(): void
    {
        $this->listener9
            ->expects(self::atLeastOnce())
            ->method('id')
            ->willReturn($this->id1)
        ;

        $state1 = InMemoryState::fromArray(['version' => 1]);
        $state2 = InMemoryState::fromArray(['version' => 2]);
        $state3 = InMemoryState::fromArray(['version' => 3]);
        $state4 = InMemoryState::fromArray(['version' => 4]);
        $state5 = InMemoryState::fromArray(['version' => 5]);
        $state6 = InMemoryState::fromArray(['version' => 6]);

        $this->listener9
            ->expects(self::exactly(6))
            ->method('toState')
            ->with(InMemoryState::empty())
            ->willReturnOnConsecutiveCalls(
                $state1,
                $state2,
                $state3,
                $state4,
                $state5,
                $state6
            )
        ;

        $this->listener9
            ->expects(self::exactly(5))
            ->method('fromState')
            ->withConsecutive(
                [$state1],
                [$state2],
                [$state3],
                [$state4],
                [$state5]
            )
        ;

        $subscription = new Subscription($this->listener9, $this->clock);

        self::assertSame($subscription->listener(), $this->listener9);
        self::assertSame($this->id1, $subscription->id());
        self::assertSame(0, $subscription->version());
        self::assertFalse($subscription->starting());

        $subscription->startFor($this->event1);

        self::assertSame($this->id1, $subscription->id());
        self::assertSame(1, $subscription->version());
        self::assertTrue($subscription->starting());

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

        $this->listener9
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

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event1, $this->event2], $events);
        self::assertSame(3, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event3, $this->event4], $events);
        self::assertSame(5, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event5], $events);
        self::assertSame(6, $subscription->version());
    }

    public function testListenerWithPicker(): void
    {
        $this->listener7
            ->expects(self::atLeastOnce())
            ->method('id')
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
        self::assertSame($this->id1, $subscription->id());
        self::assertSame(0, $subscription->version());

        $subscription->startFor($this->event3);

        self::assertSame($this->id1, $subscription->id());
        self::assertSame(1, $subscription->version());

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
        self::assertSame(3, $subscription->version());

        $subscription->restart();

        self::assertSame(4, $subscription->version());

        $events = $subscription->subscribeTo($this->store); // after restart picker should pick $this->event1
        $events = iterator_to_array($events);

        self::assertEquals([$this->event1, $this->event2, $this->event3, $this->event4], $events);
        self::assertSame(8, $subscription->version());
    }

    public function testListenerWithFilterer(): void
    {
        $this->stream1 = new InMemoryStream($this->event1, $this->event2, $this->event3);
        $this->stream2 = new InMemoryStream($this->event1, $this->event2, $this->event3, $this->event4, $this->event5);

        $this->listener8
            ->expects(self::atLeastOnce())
            ->method('id')
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener8, $this->clock);

        self::assertSame($subscription->listener(), $this->listener8);
        self::assertSame($this->id1, $subscription->id());
        self::assertSame(0, $subscription->version());

        $subscription->startFor($this->event2);

        self::assertSame(1, $subscription->version());

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
                [self::equalTo((new InMemoryStream($this->event1, $this->event2, $this->event3)))],
                [self::equalTo((new InMemoryStream($this->event1, $this->event2, $this->event3, $this->event4, $this->event5)))]
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
        self::assertSame(3, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event4, $this->event5], $events);
        self::assertSame(5, $subscription->version());
    }

    public function testTransactionalListenerWithoutReplaying(): void
    {
        $this->listener3
            ->expects(self::atLeastOnce())
            ->method('id')
            ->willReturn($this->id1)
        ;
        $this->listener1
            ->expects(self::never())
            ->method('replay')
        ;

        $subscription = new Subscription($this->listener3, $this->clock);

        self::assertSame($subscription->listener(), $this->listener3);
        self::assertSame($this->id1, $subscription->id());
        self::assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        self::assertSame($this->id1, $subscription->id());
        self::assertSame(1, $subscription->version());

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
        self::assertSame(3, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event3, $this->event4], $events);
        self::assertSame(5, $subscription->version());

        $events = $subscription->subscribeTo($this->store, \PHP_INT_MAX); // subscription will stop listening to #events right after it being completed, even if $limit was not exhausted.
        $events = iterator_to_array($events);

        self::assertEquals([$this->event5], $events);
        self::assertSame(6, $subscription->version());
    }

    public function testCompletingListener(): void
    {
        $this->listener3
            ->expects(self::atLeastOnce())
            ->method('id')
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener3, $this->clock);

        self::assertSame($subscription->listener(), $this->listener3);
        self::assertSame($this->id1, $subscription->id());
        self::assertSame(0, $subscription->version());
        self::assertFalse($subscription->completed());
        self::assertFalse($subscription->paused());

        $subscription->startFor($this->event1);

        self::assertSame($this->id1, $subscription->id());
        self::assertSame(1, $subscription->version());
        self::assertFalse($subscription->completed());
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
        self::assertSame(3, $subscription->version());
        self::assertFalse($subscription->completed());
        self::assertFalse($subscription->paused());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event3, $this->event4], $events);
        self::assertSame(5, $subscription->version());
        self::assertFalse($subscription->paused());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event5], $events);
        self::assertSame(6, $subscription->version());
        self::assertTrue($subscription->completed());
        self::assertFalse($subscription->paused());

        $subscription->pause();

        self::assertEquals([$this->event5], $events);
        self::assertSame(6, $subscription->version());
        self::assertTrue($subscription->completed());
        self::assertFalse($subscription->paused());

        $subscription->unpause();

        self::assertEquals([$this->event5], $events);
        self::assertSame(6, $subscription->version());
        self::assertTrue($subscription->completed());
        self::assertFalse($subscription->paused());

        $this->expectExceptionObject(new Event\Subscription\Exception\SubscriptionAlreadyCompleted($subscription));

        $events = $subscription->subscribeTo($this->store);

        iterator_to_array($events);
    }

    public function testStartingAlreadyStartedSubscription(): void
    {
        $this->listener1
            ->expects(self::atLeastOnce())
            ->method('id')
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener1, $this->clock);

        self::assertSame($subscription->listener(), $this->listener1);
        self::assertSame($this->id1, $subscription->id());

        $subscription->startFor($this->event1);

        $this->expectExceptionObject(new Event\Subscription\Exception\SubscriptionAlreadyStarted($subscription));

        $subscription->startFor($this->event2);
    }

    public function testStartingStatefulSubscription(): void
    {
        $this->listener9
            ->expects(self::atLeastOnce())
            ->method('id')
            ->willReturn($this->id1)
        ;
        $this->listener9
            ->expects(self::once())
            ->method('toState')
            ->willReturn(InMemoryState::empty())
        ;

        $subscription = new Subscription($this->listener9, $this->clock);

        self::assertSame($subscription->listener(), $this->listener9);
        self::assertSame($this->id1, $subscription->id());

        $subscription->startFor($this->event1);
    }

    public function testNotStartedListener(): void
    {
        $this->listener1
            ->expects(self::atLeastOnce())
            ->method('id')
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener1, $this->clock);

        self::assertSame($subscription->listener(), $this->listener1);
        self::assertSame($this->id1, $subscription->id());

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

    public function testRestartingSubscription(): void
    {
        $this->listener4
            ->expects(self::atLeastOnce())
            ->method('id')
            ->willReturn($this->id1)
        ;
        $this->listener4
            ->expects(self::once())
            ->method('reset')
        ;

        $subscription = new Subscription($this->listener4, $this->clock);

        self::assertSame($subscription->listener(), $this->listener4);
        self::assertSame($this->id1, $subscription->id());
        self::assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        self::assertSame($this->id1, $subscription->id());
        self::assertSame(1, $subscription->version());

        $this->stream1 = new InMemoryStream($this->event1, $this->event2);

        $this->store
            ->expects(self::once())
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream1
            )
        ;

        $this->listener4
            ->expects(self::exactly(2))
            ->method('on')
            ->withConsecutive(
                [$this->event1],
                [$this->event2]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true
            )
        ;
        $this->listener3
            ->expects(self::never())
            ->method('completed')
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event1, $this->event2], $events);
        self::assertSame(3, $subscription->version());

        $subscription->restart();

        self::assertSame(4, $subscription->version());

        $subscription->restart();

        self::assertSame(4, $subscription->version());
    }

    public function testRestartingNotStartedSubscription(): void
    {
        $this->listener1
            ->expects(self::atLeastOnce())
            ->method('id')
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener1, $this->clock);

        self::assertSame($subscription->listener(), $this->listener1);
        self::assertSame($this->id1, $subscription->id());

        $this->expectExceptionObject(new Event\Subscription\Exception\SubscriptionNotStartedYet($subscription));

        $subscription->restart();
    }

    public function testRestartingCompletedSubscription(): void
    {
        $this->listener3
            ->expects(self::atLeastOnce())
            ->method('id')
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener3, $this->clock);

        self::assertSame($subscription->listener(), $this->listener3);
        self::assertSame($this->id1, $subscription->id());
        self::assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        self::assertSame($this->id1, $subscription->id());
        self::assertSame(1, $subscription->version());

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
        self::assertSame(3, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event3, $this->event4], $events);
        self::assertSame(5, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event5], $events);
        self::assertSame(6, $subscription->version());
        self::assertTrue($subscription->completed());

        $subscription->restart();

        self::assertSame(7, $subscription->version());
    }

    public function testRestartingNonResettableListener(): void
    {
        $this->listener1
            ->expects(self::atLeastOnce())
            ->method('id')
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener1, $this->clock);

        self::assertSame($subscription->listener(), $this->listener1);
        self::assertSame($this->id1, $subscription->id());
        self::assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        self::assertSame($this->id1, $subscription->id());
        self::assertSame(1, $subscription->version());

        $this->stream1 = new InMemoryStream($this->event1, $this->event2);

        $this->store
            ->expects(self::once())
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream1
            )
        ;

        $this->listener1
            ->expects(self::exactly(2))
            ->method('on')
            ->withConsecutive(
                [$this->event1],
                [$this->event2]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true
            )
        ;
        $this->listener3
            ->expects(self::never())
            ->method('completed')
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event1, $this->event2], $events);
        self::assertSame(3, $subscription->version());

        $subscription->restart();

        self::assertSame(4, $subscription->version());
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
        $events = new \IteratorIterator($events);
        $events->rewind();
    }

    public function testNonPositiveLimitGivenWhileSubscribingToTheEventStoreAfterStarting(): void
    {
        $this->listener1
            ->expects(self::atLeastOnce())
            ->method('id')
            ->with()
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener1, $this->clock);
        self::assertSame($this->id1, $subscription->id());
        $subscription->startFor($this->event1);

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
        $events = new \IteratorIterator($events);
        $events->rewind();
    }

    public function testContinuousListeningWithNumberOfEventsBeingExactlyImposedLimit(): void
    {
        $this->listener1
            ->expects(self::atLeastOnce())
            ->method('id')
            ->willReturn($this->id1)
        ;
        $this->listener1
            ->expects(self::never())
            ->method('replay')
        ;

        $subscription = new Subscription($this->listener1, $this->clock);

        self::assertSame($subscription->listener(), $this->listener1);
        self::assertSame($this->id1, $subscription->id());
        self::assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        self::assertSame($this->id1, $subscription->id());
        self::assertSame(1, $subscription->version());

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
        self::assertSame(6, $subscription->version());
    }

    public function testContinuousListeningWithNumberOfEventsBeingMoreThanImposedLimit1(): void
    {
        $this->listener1
            ->expects(self::atLeastOnce())
            ->method('id')
            ->willReturn($this->id1)
        ;
        $this->listener1
            ->expects(self::never())
            ->method('replay')
        ;

        $subscription = new Subscription($this->listener1, $this->clock);

        self::assertSame($subscription->listener(), $this->listener1);
        self::assertSame($this->id1, $subscription->id());
        self::assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        self::assertSame($this->id1, $subscription->id());
        self::assertSame(1, $subscription->version());

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
        self::assertSame(5, $subscription->version());
    }

    public function testContinuousListeningWithNumberOfEventsBeingMoreThanImposedLimit2(): void
    {
        $this->listener1
            ->expects(self::atLeastOnce())
            ->method('id')
            ->willReturn($this->id1)
        ;
        $this->listener1
            ->expects(self::never())
            ->method('replay')
        ;

        $subscription = new Subscription($this->listener1, $this->clock);

        self::assertSame($subscription->listener(), $this->listener1);
        self::assertSame($this->id1, $subscription->id());
        self::assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        self::assertSame($this->id1, $subscription->id());
        self::assertSame(1, $subscription->version());

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
        self::assertSame(5, $subscription->version());
    }

    public function testContinuousListeningWithNumberOfEventsBeingLessThanImposedLimit1(): void
    {
        $this->listener1
            ->expects(self::atLeastOnce())
            ->method('id')
            ->willReturn($this->id1)
        ;
        $this->listener1
            ->expects(self::never())
            ->method('replay')
        ;

        $subscription = new Subscription($this->listener1, $this->clock);

        self::assertSame($subscription->listener(), $this->listener1);
        self::assertSame($this->id1, $subscription->id());
        self::assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        self::assertSame($this->id1, $subscription->id());
        self::assertSame(1, $subscription->version());

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
        self::assertSame(6, $subscription->version());
    }

    public function testCompletingListenerWhileContinuousListening(): void
    {
        $this->listener3
            ->expects(self::atLeastOnce())
            ->method('id')
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener3, $this->clock);

        self::assertSame($subscription->listener(), $this->listener3);
        self::assertSame($this->id1, $subscription->id());
        self::assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        self::assertSame($this->id1, $subscription->id());
        self::assertSame(1, $subscription->version());

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
        self::assertSame(6, $subscription->version());
    }
}

namespace Streak\Infrastructure\Domain\Event\Subscription\DAO\SubscriptionTest;

use Streak\Domain\Event;
use Streak\Domain\Event\Listener;

abstract class CompletableListener implements Listener, Listener\Completable
{
}

abstract class ResettableListener implements Listener, Listener\Resettable
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
