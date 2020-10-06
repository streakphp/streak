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

namespace Streak\Infrastructure\Event\Subscription\DAO;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Domain\EventStore;
use Streak\Domain\Id\UUID;
use Streak\Infrastructure\Event\InMemoryStream;
use Streak\Infrastructure\Event\Sourced\Subscription\InMemoryState;
use Streak\Infrastructure\FixedClock;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Event\Subscription\DAO\Subscription
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

        $this->state1 = InMemoryState::fromArray(['number' => 1]);
        $this->state2 = InMemoryState::fromArray(['number' => 2]);
        $this->state3 = InMemoryState::fromArray(['number' => 3]);

        $this->clock = new FixedClock(new \DateTime('2018-09-28 19:12:32.763188 +00:00'));
    }

    public function testListener()
    {
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
        $this->assertSame(0, $subscription->version());
        $this->assertFalse($subscription->starting());
        $this->assertFalse($subscription->paused());

        $subscription->pause();
        $subscription->unpause();

        $subscription->startFor($this->event1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame(1, $subscription->version());
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
        $this->assertSame(3, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event3, $this->event4], $events);
        $this->assertSame(5, $subscription->version());
        $this->assertFalse($subscription->paused());

        $subscription->pause();

        $this->assertTrue($subscription->paused());

        $subscription->pause();

        $this->assertTrue($subscription->paused());

        try {
            $events = $subscription->subscribeTo($this->store);
            iterator_to_array($events);
        } catch (Event\Subscription\Exception\SubscriptionPaused $exception) {
            $this->assertSame($subscription, $exception->subscription());
        } finally {
            $this->assertTrue(isset($exception));
        }

        $subscription->unpause();

        $this->assertFalse($subscription->paused());

        $subscription->unpause();

        $this->assertFalse($subscription->paused());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event5], $events);
        $this->assertSame(6, $subscription->version());
        $this->assertFalse($subscription->paused());
    }

    public function testStatefulListener()
    {
        $this->listener9
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;

        $state1 = InMemoryState::fromArray(['version' => 1]);
        $state2 = InMemoryState::fromArray(['version' => 2]);
        $state3 = InMemoryState::fromArray(['version' => 3]);
        $state4 = InMemoryState::fromArray(['version' => 4]);
        $state5 = InMemoryState::fromArray(['version' => 5]);
        $state6 = InMemoryState::fromArray(['version' => 6]);

        $this->listener9
            ->expects($this->exactly(6))
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
            ->expects($this->exactly(5))
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

        $this->assertSame($subscription->listener(), $this->listener9);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame(0, $subscription->version());
        $this->assertFalse($subscription->starting());

        $subscription->startFor($this->event1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame(1, $subscription->version());
        $this->assertTrue($subscription->starting());

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

        $this->listener9
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
        $this->assertSame(3, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event3, $this->event4], $events);
        $this->assertSame(5, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event5], $events);
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
        $this->assertSame(0, $subscription->version());

        $subscription->startFor($this->event3);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame(1, $subscription->version());

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
        $this->assertSame(3, $subscription->version());

        $subscription->restart();

        $this->assertSame(4, $subscription->version());

        $events = $subscription->subscribeTo($this->store); // after restart picker should pick $this->event1
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1, $this->event2, $this->event3, $this->event4], $events);
        $this->assertSame(8, $subscription->version());
    }

    public function testListenerWithFilterer()
    {
        $this->stream1 = new InMemoryStream($this->event1, $this->event2, $this->event3);
        $this->stream2 = new InMemoryStream($this->event1, $this->event2, $this->event3, $this->event4, $this->event5);

        $this->listener8
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener8, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener8);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame(0, $subscription->version());

        $subscription->startFor($this->event2);

        $this->assertSame(1, $subscription->version());

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
                [$this->equalTo((new InMemoryStream($this->event1, $this->event2, $this->event3)))],
                [$this->equalTo((new InMemoryStream($this->event1, $this->event2, $this->event3, $this->event4, $this->event5)))]
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
        $this->assertSame(3, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event4, $this->event5], $events);
        $this->assertSame(5, $subscription->version());
    }

    public function testTransactionalListenerWithoutReplaying()
    {
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
        $this->assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame(1, $subscription->version());

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
        $this->assertSame(3, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event3, $this->event4], $events);
        $this->assertSame(5, $subscription->version());

        $events = $subscription->subscribeTo($this->store, PHP_INT_MAX); // subscription will stop listening to #events right after it being completed, even if $limit was not exhausted.
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event5], $events);
        $this->assertSame(6, $subscription->version());
    }

    public function testCompletingListener()
    {
        $this->listener3
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener3, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener3);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame(0, $subscription->version());
        $this->assertFalse($subscription->completed());
        $this->assertFalse($subscription->paused());

        $subscription->startFor($this->event1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame(1, $subscription->version());
        $this->assertFalse($subscription->completed());
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
        $this->assertSame(3, $subscription->version());
        $this->assertFalse($subscription->completed());
        $this->assertFalse($subscription->paused());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event3, $this->event4], $events);
        $this->assertSame(5, $subscription->version());
        $this->assertFalse($subscription->paused());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event5], $events);
        $this->assertSame(6, $subscription->version());
        $this->assertTrue($subscription->completed());
        $this->assertFalse($subscription->paused());

        $subscription->pause();

        $this->assertEquals([$this->event5], $events);
        $this->assertSame(6, $subscription->version());
        $this->assertTrue($subscription->completed());
        $this->assertFalse($subscription->paused());

        $subscription->unpause();

        $this->assertEquals([$this->event5], $events);
        $this->assertSame(6, $subscription->version());
        $this->assertTrue($subscription->completed());
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

        $subscription->startFor($this->event1);

        $this->expectExceptionObject(new Event\Subscription\Exception\SubscriptionAlreadyStarted($subscription));

        $subscription->startFor($this->event2);
    }

    public function testStartingStatefulSubscription()
    {
        $this->listener9
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $this->listener9
            ->expects($this->once())
            ->method('toState')
            ->willReturn(InMemoryState::empty())
        ;

        $subscription = new Subscription($this->listener9, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener9);
        $this->assertSame($this->id1, $subscription->subscriptionId());

        $subscription->startFor($this->event1);
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

    public function testRestartingSubscription()
    {
        $this->listener4
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;
        $this->listener4
            ->expects($this->once())
            ->method('reset')
        ;

        $subscription = new Subscription($this->listener4, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener4);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame(1, $subscription->version());

        $this->stream1 = new InMemoryStream($this->event1, $this->event2);

        $this->store
            ->expects($this->exactly(1))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream1
            )
        ;

        $this->listener4
            ->expects($this->exactly(2))
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
            ->expects($this->never())
            ->method('completed')
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1, $this->event2], $events);
        $this->assertSame(3, $subscription->version());

        $subscription->restart();

        $this->assertSame(4, $subscription->version());

        $subscription->restart();

        $this->assertSame(4, $subscription->version());
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

        $this->expectExceptionObject(new Event\Subscription\Exception\SubscriptionNotStartedYet($subscription));

        $subscription->restart();
    }

    public function testRestartingCompletedSubscription()
    {
        $this->listener3
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener3, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener3);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame(1, $subscription->version());

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
        $this->assertSame(3, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event3, $this->event4], $events);
        $this->assertSame(5, $subscription->version());

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event5], $events);
        $this->assertSame(6, $subscription->version());
        $this->assertTrue($subscription->completed());

        $subscription->restart();

        $this->assertSame(7, $subscription->version());
    }

    public function testRestartingNonResettableListener()
    {
        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener1, $this->clock);

        $this->assertSame($subscription->listener(), $this->listener1);
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame(1, $subscription->version());

        $this->stream1 = new InMemoryStream($this->event1, $this->event2);

        $this->store
            ->expects($this->exactly(1))
            ->method('stream')
            ->willReturnOnConsecutiveCalls(
                $this->stream1
            )
        ;

        $this->listener1
            ->expects($this->exactly(2))
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
            ->expects($this->never())
            ->method('completed')
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1, $this->event2], $events);
        $this->assertSame(3, $subscription->version());

        $subscription->restart();

        $this->assertSame(4, $subscription->version());
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
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $subscription->startFor($this->event1);

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
        $this->assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame(1, $subscription->version());

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
        $this->assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame(1, $subscription->version());

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
        $this->assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame(1, $subscription->version());

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
        $this->assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame(1, $subscription->version());

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
        $this->assertSame(0, $subscription->version());

        $subscription->startFor($this->event1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame(1, $subscription->version());

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
        $this->assertSame(6, $subscription->version());
    }
}
