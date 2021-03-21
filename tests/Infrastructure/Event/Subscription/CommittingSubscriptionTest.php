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
use Streak\Domain\Clock;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Domain\EventStore;
use Streak\Domain\Id\UUID;
use Streak\Infrastructure\Event\Subscription\CommittingSubscriptionTest\IterableStream;
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
     * @var Event\Subscription|MockObject
     */
    protected $subscription;
    /**
     * @var Listener|MockObject
     */
    private $listener;

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

    private ?Clock $clock = null;

    /**
     * @var UnitOfWork|MockObject
     */
    private $uow;

    public function setUp() : void
    {
        $this->listener = $this->getMockBuilder(Listener::class)->setMethods(['replay', 'reset', 'completed'])->getMockForAbstractClass();

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

        $this->clock = new FixedClock(new \DateTime('2018-09-28 19:12:32.763188 +00:00'));

        $this->uow = $this->getMockBuilder(UnitOfWork::class)->getMockForAbstractClass();

        $this->subscription = $this->getMockBuilder(Event\Subscription::class)->getMock();
    }

    public function testListener()
    {
        $subscription = new CommittingSubscription($this->subscription, $this->uow);

        $this->assertSame($this->subscription, $subscription->subscription());

        $this->subscription
            ->expects($this->atLeastOnce())
            ->method('listener')
            ->willReturn($this->listener)
        ;
        $this->subscription
            ->expects($this->atLeastOnce())
            ->method('subscriptionId')
            ->willReturn($this->id1)
        ;
        $this->subscription
            ->expects($this->atLeastOnce())
            ->method('version')
            ->willReturn(PHP_INT_MAX)
        ;
        $this->subscription
            ->expects($this->exactly(2))
            ->method('starting')
            ->willReturnOnConsecutiveCalls(true, false)
        ;
        $this->subscription
            ->expects($this->exactly(2))
            ->method('started')
            ->willReturnOnConsecutiveCalls(true, false)
        ;
        $this->subscription
            ->expects($this->exactly(2))
            ->method('completed')
            ->willReturnOnConsecutiveCalls(true, false)
        ;
        $this->subscription
            ->expects($this->exactly(2))
            ->method('paused')
            ->willReturnOnConsecutiveCalls(true, false)
        ;

        $this->assertSame($this->listener, $subscription->listener());
        $this->assertSame($this->id1, $subscription->subscriptionId());
        $this->assertSame(PHP_INT_MAX, $subscription->version());
        $this->assertTrue($subscription->starting());
        $this->assertFalse($subscription->starting());
        $this->assertTrue($subscription->started());
        $this->assertFalse($subscription->started());
        $this->assertTrue($subscription->completed());
        $this->assertFalse($subscription->completed());
        $this->assertTrue($subscription->paused());
        $this->assertFalse($subscription->paused());
    }

    public function testStartFor()
    {
        $subscription = new CommittingSubscription($this->subscription, $this->uow);

        $this->uow
            ->expects($this->once())
            ->method('add')
            ->with($this->subscription)
        ;

        $this->subscription
            ->expects($this->once())
            ->method('startFor')
            ->with($this->event1)
        ;

        $this->uow
            ->expects($this->once())
            ->method('commit')
        ;

        $this->uow
            ->expects($this->never())
            ->method('clear')
        ;

        $subscription->startFor($this->event1);
    }

    public function testStartForWithError()
    {
        $exception = new \RuntimeException('test');
        $this->expectExceptionObject($exception);

        $subscription = new CommittingSubscription($this->subscription, $this->uow);

        $this->uow
            ->expects($this->once())
            ->method('add')
            ->with($this->subscription)
        ;

        $this->subscription
            ->expects($this->once())
            ->method('startFor')
            ->with($this->event1)
            ->willThrowException($exception)
        ;

        $this->uow
            ->expects($this->never())
            ->method('commit')
        ;

        $this->uow
            ->expects($this->once())
            ->method('clear')
        ;

        $subscription->startFor($this->event1);
    }

    public function testSubscribeTo()
    {
        $subscription = new CommittingSubscription($this->subscription, $this->uow);

        $this->uow
            ->expects($this->atLeastOnce())
            ->method('add')
            ->with($this->subscription)
        ;

        $this->subscription
            ->expects($this->once())
            ->method('subscribeTo')
            ->with($this->store, null)
            ->willReturnCallback(function () {
                yield $this->event1;
            })
        ;

        $this->uow
            ->expects($this->atLeastOnce())
            ->method('commit')
        ;

        $this->uow
            ->expects($this->never())
            ->method('clear')
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        $this->assertIsArray($events);
        $this->assertNotEmpty($events);
        $this->assertSame($this->event1, $events[0]);
    }

    public function testSubscribeToWithError()
    {
        $exception = new \RuntimeException('test');
        $this->expectExceptionObject($exception);

        $subscription = new CommittingSubscription($this->subscription, $this->uow);

        $this->uow
            ->expects($this->atLeastOnce())
            ->method('add')
            ->with($this->subscription)
        ;

        $this->subscription
            ->expects($this->once())
            ->method('subscribeTo')
            ->with($this->store, null)
            ->willThrowException($exception)
        ;

        $this->uow
            ->expects($this->never())
            ->method('commit')
        ;

        $this->uow
            ->expects($this->once())
            ->method('clear')
        ;

        $events = $subscription->subscribeTo($this->store);
        iterator_to_array($events);
    }

    public function testRestart()
    {
        $subscription = new CommittingSubscription($this->subscription, $this->uow);

        $this->uow
            ->expects($this->once())
            ->method('add')
            ->with($this->subscription)
        ;

        $this->subscription
            ->expects($this->once())
            ->method('restart')
            ->with()
        ;

        $this->uow
            ->expects($this->once())
            ->method('commit')
        ;

        $this->uow
            ->expects($this->never())
            ->method('clear')
        ;

        $subscription->restart();
    }

    public function testRestartWithError()
    {
        $exception = new \RuntimeException('test');
        $this->expectExceptionObject($exception);

        $subscription = new CommittingSubscription($this->subscription, $this->uow);

        $this->uow
            ->expects($this->once())
            ->method('add')
            ->with($this->subscription)
        ;

        $this->subscription
            ->expects($this->once())
            ->method('restart')
            ->with()
            ->willThrowException($exception)
        ;

        $this->uow
            ->expects($this->never())
            ->method('commit')
        ;

        $this->uow
            ->expects($this->once())
            ->method('clear')
        ;

        $subscription->restart();
    }

    public function testPause()
    {
        $subscription = new CommittingSubscription($this->subscription, $this->uow);

        $this->uow
            ->expects($this->once())
            ->method('add')
            ->with($this->subscription)
        ;

        $this->subscription
            ->expects($this->once())
            ->method('pause')
            ->with()
        ;

        $this->uow
            ->expects($this->once())
            ->method('commit')
        ;

        $this->uow
            ->expects($this->never())
            ->method('clear')
        ;

        $subscription->pause();
    }

    public function testPauseWithError()
    {
        $exception = new \RuntimeException('test');
        $this->expectExceptionObject($exception);

        $subscription = new CommittingSubscription($this->subscription, $this->uow);

        $this->uow
            ->expects($this->once())
            ->method('add')
            ->with($this->subscription)
        ;

        $this->subscription
            ->expects($this->once())
            ->method('pause')
            ->with()
            ->willThrowException($exception)
        ;

        $this->uow
            ->expects($this->never())
            ->method('commit')
        ;

        $this->uow
            ->expects($this->once())
            ->method('clear')
        ;

        $subscription->pause();
    }

    public function testUnpause()
    {
        $subscription = new CommittingSubscription($this->subscription, $this->uow);

        $this->uow
            ->expects($this->once())
            ->method('add')
            ->with($this->subscription)
        ;

        $this->subscription
            ->expects($this->once())
            ->method('unpause')
            ->with()
        ;

        $this->uow
            ->expects($this->once())
            ->method('commit')
        ;

        $this->uow
            ->expects($this->never())
            ->method('clear')
        ;

        $subscription->unpause();
    }

    public function testUnpauseWithError()
    {
        $exception = new \RuntimeException('test');
        $this->expectExceptionObject($exception);

        $subscription = new CommittingSubscription($this->subscription, $this->uow);

        $this->uow
            ->expects($this->once())
            ->method('add')
            ->with($this->subscription)
        ;

        $this->subscription
            ->expects($this->once())
            ->method('unpause')
            ->with()
            ->willThrowException($exception)
        ;

        $this->uow
            ->expects($this->never())
            ->method('commit')
        ;

        $this->uow
            ->expects($this->once())
            ->method('clear')
        ;

        $subscription->unpause();
    }
}

namespace Streak\Infrastructure\Event\Subscription\CommittingSubscriptionTest;

use Streak\Domain\Event;

abstract class IterableStream implements Event\Stream, \IteratorAggregate
{
}
