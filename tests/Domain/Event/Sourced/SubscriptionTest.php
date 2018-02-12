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
use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionCompleted;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionListenedToEvent;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionStarted;
use Streak\Domain\EventStore;

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
     * @var Listener|Event\Replayable|MockObject
     */
    private $listener2;

    /**
     * @var Listener|Event\Completable|MockObject
     */
    private $listener3;

    /**
     * @var Domain\Id|MockObject
     */
    private $id1;

    /**
     * @var Domain\Id|MockObject
     */
    private $id2;

    /**
     * @var EventStore|MockObject
     */
    private $store;

    /**
     * @var Event\Log|MockObject
     */
    private $log;

    /**
     * @var Event\Stream|MockObject
     */
    private $stream1;

    /**
     * @var Event\Stream|MockObject
     */
    private $stream2;

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

    public function setUp()
    {
        $this->markTestSkipped('Fix it!');

        $this->listener1 = $this->getMockBuilder(Listener::class)->getMockForAbstractClass();
        $this->listener2 = $this->getMockBuilder([Listener::class, Event\Replayable::class])->getMock();
        $this->listener3 = $this->getMockBuilder([Listener::class, Event\Completable::class])->getMock();

        $this->id1 = $this->getMockBuilder(Domain\Id::class)->getMockForAbstractClass();
        $this->id2 = $this->getMockBuilder(Domain\Id::class)->getMockForAbstractClass();

        $this->store = $this->getMockBuilder(EventStore::class)->getMockForAbstractClass();

        $this->log = $this->getMockBuilder(Event\Log::class)->getMockForAbstractClass();

        $this->stream1 = $this->getMockBuilder(Event\FilterableStream::class)->getMockForAbstractClass();
        $this->stream2 = $this->getMockBuilder(Event\FilterableStream::class)->getMockForAbstractClass();

        $this->event1 = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $this->event2 = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $this->event3 = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
    }

    public function testObject()
    {
        $now = new \DateTime();

        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('id')
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->last());
        $this->assertEmpty($subscription->events());

        $subscription->start($now);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertEquals(new SubscriptionStarted($now), $subscription->last());
        $this->assertEquals([new SubscriptionStarted($now)], $subscription->events());

        $this->store
            ->expects($this->once())
            ->method('stream')
            ->willReturn($this->stream2)
        ;

        $this->stream2
            ->expects($this->never())
            ->method('after')
        ;

        $this->isIteratorFor($this->stream2, [$this->event3]);

        $this->listener1
            ->expects($this->once())
            ->method('on')
            ->with($this->event3)
            ->willReturn(true)
        ;

        $events = $subscription->subscribeTo($this->store);

        $this->assertEquals([$this->event3], iterator_to_array($events));

        $this->assertEquals([new SubscriptionStarted($now), new SubscriptionListenedToEvent($this->event1)], $subscription->events());
    }

    public function testNonReplayableListener()
    {
        $now = new \DateTime();
        $subscription = new Subscription($this->listener1);

        $event1 = new SubscriptionStarted($now);
        $event2 = new SubscriptionListenedToEvent($this->event1);

        $this->isIteratorFor($this->stream1, [$event1, $event2]);

        $subscription->replay($this->stream1);

        $this->assertSame($event2, $subscription->lastReplayed());
        $this->assertSame($event2, $subscription->last());
        $this->assertEmpty($subscription->events());

        $this->store
            ->expects($this->once())
            ->method('stream')
            ->willReturn($this->stream2)
        ;

        $this->stream2
            ->expects($this->once())
            ->method('after')
            ->with($event2)
            ->willReturnSelf()
        ;

        $this->isIteratorFor($this->stream2, [$this->event3]);

        $this->listener1
            ->expects($this->once())
            ->method('on')
            ->with($this->event3)
            ->willReturn(true)
        ;

        $events = $subscription->subscribeTo($this->store);

        $this->assertEquals([$this->event3], iterator_to_array($events));

        $this->assertEquals([new SubscriptionListenedToEvent($this->event3)], $subscription->events());
    }

    public function testReplayableListener()
    {
        $now = new \DateTime();
        $subscription = new Subscription($this->listener2);

        $event1 = new SubscriptionStarted($now);
        $event2 = new SubscriptionListenedToEvent($this->event1);

        $this->isIteratorFor($this->stream1, [$event1, $event2]);

        $this->listener2
            ->expects($this->once())
            ->method('replay')
            ->with($this->callback(function ($stream) use ($event1, $event2) {
                $this->assertInstanceOf(Event\Stream::class, $stream);
                $this->assertEquals([$event2->event()], iterator_to_array($stream));

                return true;
            }))
        ;

        $subscription->replay($this->stream1);

        $this->assertSame($event2, $subscription->lastReplayed());
        $this->assertSame($event2, $subscription->last());
        $this->assertEmpty($subscription->events());

        $this->store
            ->expects($this->once())
            ->method('stream')
            ->willReturn($this->stream2)
        ;

        $this->stream2
            ->expects($this->once())
            ->method('after')
            ->with($event2)
            ->willReturnSelf()
        ;

        $this->isIteratorFor($this->stream2, [$this->event3]);

        $this->listener2
            ->expects($this->once())
            ->method('on')
            ->with($this->event3)
            ->willReturn(true)
        ;

        $events = $subscription->subscribeTo($this->store);

        $this->assertEquals([$this->event3], iterator_to_array($events));

        $this->assertEquals([new SubscriptionListenedToEvent($this->event3)], $subscription->events());
    }

    public function testCompletableListener()
    {
        $now = new \DateTime();
        $subscription = new Subscription($this->listener3);

        $this->assertFalse($subscription->completed());

        $event1 = new SubscriptionStarted($now);

        $this->isIteratorFor($this->stream1, [$event1]);

        $subscription->replay($this->stream1);

        $this->assertSame($event1, $subscription->lastReplayed());
        $this->assertSame($event1, $subscription->last());
        $this->assertEmpty($subscription->events());

        $this->store
            ->expects($this->once())
            ->method('stream')
            ->willReturn($this->stream2)
        ;

        $this->stream2
            ->expects($this->once())
            ->method('after')
            ->with($event1)
            ->willReturnSelf()
        ;

        $this->isIteratorFor($this->stream2, [$this->event1]);

        $this->listener3
            ->expects($this->once())
            ->method('on')
            ->with($this->event1)
            ->willReturn(true)
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

        $this->assertEquals([], iterator_to_array($events));

        $this->assertEquals([new SubscriptionListenedToEvent($this->event1), new SubscriptionCompleted()], $subscription->events());
    }

    public function testSubscribingAlreadyCompletedListener()
    {
        $now = new \DateTime();
        $subscription = new Subscription($this->listener3);

        $this->assertFalse($subscription->completed());

        $event1 = new SubscriptionStarted($now);

        $this->isIteratorFor($this->stream1, [$event1]);

        $subscription->replay($this->stream1);

        $this->assertSame($event1, $subscription->lastReplayed());
        $this->assertSame($event1, $subscription->last());
        $this->assertEmpty($subscription->events());

        $this->store
            ->expects($this->once())
            ->method('stream')
            ->willReturn($this->stream2)
        ;

        $this->stream2
            ->expects($this->once())
            ->method('after')
            ->with($event1)
            ->willReturnSelf()
        ;

        $this->isIteratorFor($this->stream2, [$this->event1]);

        $this->listener3
            ->expects($this->never())
            ->method('on')
        ;

        $this->listener3
            ->expects($this->once())
            ->method('completed')
            ->willReturn(true);

        $events = $subscription->subscribeTo($this->store);

        $this->assertEquals([], iterator_to_array($events));

        $this->assertEquals([], $subscription->events());
    }

    public function testNotStartedListener()
    {
        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('id')
            ->willReturn($this->id1)
        ;

        $subscription = new Subscription($this->listener1);

        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame($this->id1, $subscription->producerId());
        $this->assertNull($subscription->lastReplayed());
        $this->assertNull($subscription->last());
        $this->assertEmpty($subscription->events());

        $this->store
            ->expects($this->once())
            ->method('stream')
            ->willReturn($this->stream2)
        ;

        $this->stream2
            ->expects($this->never())
            ->method('after')
        ;

        $this->isIteratorFor($this->stream2, [$this->event3]);

        $this->listener1
            ->expects($this->never())
            ->method('on')
        ;

        $exception = new \BadMethodCallException();
        $this->expectExceptionObject($exception);

        $events = $subscription->subscribeTo($this->store);

        $this->assertEquals([$this->event3], iterator_to_array($events));
    }

    public function testEquals()
    {
        $subscription1 = new Subscription($this->listener1);

        $this->assertFalse($subscription1->equals(new \stdClass()));

        $subscription2 = new Subscription($this->listener2);

        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('id')
            ->willReturn($this->id1)
        ;

        $this->listener2
            ->expects($this->atLeastOnce())
            ->method('id')
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
