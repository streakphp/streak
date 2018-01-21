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
        $this->listener1 = $this->getMockBuilder(Listener::class)->getMockForAbstractClass();
        $this->listener2 = $this->getMockBuilder([Listener::class, Event\Replayable::class])->getMock();
        $this->listener3 = $this->getMockBuilder([Listener::class, Event\Completable::class])->getMock();

        $this->id1 = $this->getMockBuilder(Domain\Id::class)->getMockForAbstractClass();

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

        $this->stream2
            ->expects($this->once())
            ->method('limit')
            ->with(1234)
            ->willReturnSelf()
        ;

        $this->isIteratorFor($this->stream2, [$this->event3]);

        $this->listener1
            ->expects($this->once())
            ->method('on')
            ->with($this->event3)
            ->willReturn(true)
        ;

        $subscription->subscribeTo($this->store, 1234);

        $this->assertEquals([new SubscriptionStarted(), new SubscriptionListenedToEvent($this->event1)], $subscription->events());
    }

    public function testNonReplayableListener()
    {
        $subscription = new Subscription($this->listener1);

        $event1 = new SubscriptionStarted();
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

        $this->stream2
            ->expects($this->once())
            ->method('limit')
            ->with(1234)
            ->willReturnSelf()
        ;

        $this->isIteratorFor($this->stream2, [$this->event3]);

        $this->listener1
            ->expects($this->once())
            ->method('on')
            ->with($this->event3)
            ->willReturn(true)
        ;

        $subscription->subscribeTo($this->store, 1234);

        $this->assertEquals([new SubscriptionListenedToEvent($this->event3)], $subscription->events());
    }

    public function testReplayableListener()
    {
        $subscription = new Subscription($this->listener2);

        $event1 = new SubscriptionStarted();
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

        $this->stream2
            ->expects($this->once())
            ->method('limit')
            ->with(1234)
            ->willReturnSelf()
        ;

        $this->isIteratorFor($this->stream2, [$this->event3]);

        $this->listener2
            ->expects($this->once())
            ->method('on')
            ->with($this->event3)
            ->willReturn(true)
        ;

        $subscription->subscribeTo($this->store, 1234);

        $this->assertEquals([new SubscriptionListenedToEvent($this->event3)], $subscription->events());
    }

    public function testCompletableListener()
    {
        $subscription = new Subscription($this->listener3);

        $this->assertFalse($subscription->completed());

        $event1 = new SubscriptionStarted();

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

        $this->stream2
            ->expects($this->once())
            ->method('limit')
            ->with(1234)
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
            ->expects($this->once())
            ->method('completed')
            ->willReturn(true);

        $subscription->subscribeTo($this->store, 1234);

        $this->assertEquals([new SubscriptionListenedToEvent($this->event1), new SubscriptionCompleted()], $subscription->events());
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
