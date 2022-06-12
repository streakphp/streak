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

namespace Streak\Infrastructure\Domain\Event\Subscription;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Domain\EventStore;
use Streak\Domain\Id\UUID;
use Streak\Infrastructure\Domain\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\Event\Subscription\CommittingSubscription
 */
class CommittingSubscriptionTest extends TestCase
{
    private Event\Subscription|MockObject $subscription;

    private Listener|MockObject $listener;

    private Listener\Id $id1;

    private EventStore|MockObject $store;

    private Event\Envelope $event1;

    private UnitOfWork|MockObject $uow;

    protected function setUp(): void
    {
        $this->listener = $this->getMockBuilder(Listener::class)->addMethods(['replay', 'reset', 'completed'])->getMockForAbstractClass();

        $this->id1 = new class ('f5e65690-e50d-4312-a175-b004ec1bd42a') extends UUID implements Listener\Id {
        };

        $this->store = $this->getMockBuilder(EventStore::class)->getMockForAbstractClass();

        $this->event1 = Event\Envelope::new($this->getMockBuilder(Event::class)->setMockClassName('event1')->getMockForAbstractClass(), UUID::random());

        $this->uow = $this->getMockBuilder(UnitOfWork::class)->getMockForAbstractClass();

        $this->subscription = $this->getMockBuilder(Event\Subscription::class)->getMock();
    }

    public function testListener(): void
    {
        $subscription = new CommittingSubscription($this->subscription, $this->uow);

        self::assertSame($this->subscription, $subscription->subscription());

        $this->subscription
            ->expects(self::atLeastOnce())
            ->method('listener')
            ->willReturn($this->listener)
        ;
        $this->subscription
            ->expects(self::atLeastOnce())
            ->method('id')
            ->willReturn($this->id1)
        ;
        $this->subscription
            ->expects(self::atLeastOnce())
            ->method('version')
            ->willReturn(\PHP_INT_MAX)
        ;
        $this->subscription
            ->expects(self::exactly(2))
            ->method('starting')
            ->willReturnOnConsecutiveCalls(true, false)
        ;
        $this->subscription
            ->expects(self::exactly(2))
            ->method('started')
            ->willReturnOnConsecutiveCalls(true, false)
        ;
        $this->subscription
            ->expects(self::exactly(2))
            ->method('completed')
            ->willReturnOnConsecutiveCalls(true, false)
        ;
        $this->subscription
            ->expects(self::exactly(2))
            ->method('paused')
            ->willReturnOnConsecutiveCalls(true, false)
        ;

        self::assertSame($this->listener, $subscription->listener());
        self::assertSame($this->id1, $subscription->id());
        self::assertSame(\PHP_INT_MAX, $subscription->version());
        self::assertTrue($subscription->starting());
        self::assertFalse($subscription->starting());
        self::assertTrue($subscription->started());
        self::assertFalse($subscription->started());
        self::assertTrue($subscription->completed());
        self::assertFalse($subscription->completed());
        self::assertTrue($subscription->paused());
        self::assertFalse($subscription->paused());
    }

    public function testStartFor(): void
    {
        $subscription = new CommittingSubscription($this->subscription, $this->uow);

        $this->uow
            ->expects(self::once())
            ->method('add')
            ->with($this->subscription)
        ;

        $this->subscription
            ->expects(self::once())
            ->method('startFor')
            ->with($this->event1)
        ;

        $this->uow
            ->expects(self::once())
            ->method('commit')
        ;

        $this->uow
            ->expects(self::never())
            ->method('clear')
        ;

        $subscription->startFor($this->event1);
    }

    public function testStartForWithError(): void
    {
        $exception = new \RuntimeException('test');
        $this->expectExceptionObject($exception);

        $subscription = new CommittingSubscription($this->subscription, $this->uow);

        $this->uow
            ->expects(self::once())
            ->method('add')
            ->with($this->subscription)
        ;

        $this->subscription
            ->expects(self::once())
            ->method('startFor')
            ->with($this->event1)
            ->willThrowException($exception)
        ;

        $this->uow
            ->expects(self::never())
            ->method('commit')
        ;

        $this->uow
            ->expects(self::once())
            ->method('clear')
        ;

        $subscription->startFor($this->event1);
    }

    public function testSubscribeTo(): void
    {
        $subscription = new CommittingSubscription($this->subscription, $this->uow);

        $this->uow
            ->expects(self::atLeastOnce())
            ->method('add')
            ->with($this->subscription)
        ;

        $this->subscription
            ->expects(self::once())
            ->method('subscribeTo')
            ->with($this->store, null)
            ->willReturnCallback(function () {
                yield $this->event1;
            })
        ;

        $this->uow
            ->expects(self::atLeastOnce())
            ->method('commit')
        ;

        $this->uow
            ->expects(self::never())
            ->method('clear')
        ;

        $events = $subscription->subscribeTo($this->store);
        $events = iterator_to_array($events);

        self::assertIsArray($events);
        self::assertNotEmpty($events);
        self::assertSame($this->event1, $events[0]);
    }

    public function testSubscribeToWithError(): void
    {
        $exception = new \RuntimeException('test');
        $this->expectExceptionObject($exception);

        $subscription = new CommittingSubscription($this->subscription, $this->uow);

        $this->uow
            ->expects(self::atLeastOnce())
            ->method('add')
            ->with($this->subscription)
        ;

        $this->subscription
            ->expects(self::once())
            ->method('subscribeTo')
            ->with($this->store, null)
            ->willThrowException($exception)
        ;

        $this->uow
            ->expects(self::never())
            ->method('commit')
        ;

        $this->uow
            ->expects(self::once())
            ->method('clear')
        ;

        $events = $subscription->subscribeTo($this->store);
        iterator_to_array($events);
    }

    public function testRestart(): void
    {
        $subscription = new CommittingSubscription($this->subscription, $this->uow);

        $this->uow
            ->expects(self::once())
            ->method('add')
            ->with($this->subscription)
        ;

        $this->subscription
            ->expects(self::once())
            ->method('restart')
            ->with()
        ;

        $this->uow
            ->expects(self::once())
            ->method('commit')
        ;

        $this->uow
            ->expects(self::never())
            ->method('clear')
        ;

        $subscription->restart();
    }

    public function testRestartWithError(): void
    {
        $exception = new \RuntimeException('test');
        $this->expectExceptionObject($exception);

        $subscription = new CommittingSubscription($this->subscription, $this->uow);

        $this->uow
            ->expects(self::once())
            ->method('add')
            ->with($this->subscription)
        ;

        $this->subscription
            ->expects(self::once())
            ->method('restart')
            ->with()
            ->willThrowException($exception)
        ;

        $this->uow
            ->expects(self::never())
            ->method('commit')
        ;

        $this->uow
            ->expects(self::once())
            ->method('clear')
        ;

        $subscription->restart();
    }

    public function testPause(): void
    {
        $subscription = new CommittingSubscription($this->subscription, $this->uow);

        $this->uow
            ->expects(self::once())
            ->method('add')
            ->with($this->subscription)
        ;

        $this->subscription
            ->expects(self::once())
            ->method('pause')
            ->with()
        ;

        $this->uow
            ->expects(self::once())
            ->method('commit')
        ;

        $this->uow
            ->expects(self::never())
            ->method('clear')
        ;

        $subscription->pause();
    }

    public function testPauseWithError(): void
    {
        $exception = new \RuntimeException('test');
        $this->expectExceptionObject($exception);

        $subscription = new CommittingSubscription($this->subscription, $this->uow);

        $this->uow
            ->expects(self::once())
            ->method('add')
            ->with($this->subscription)
        ;

        $this->subscription
            ->expects(self::once())
            ->method('pause')
            ->with()
            ->willThrowException($exception)
        ;

        $this->uow
            ->expects(self::never())
            ->method('commit')
        ;

        $this->uow
            ->expects(self::once())
            ->method('clear')
        ;

        $subscription->pause();
    }

    public function testUnpause(): void
    {
        $subscription = new CommittingSubscription($this->subscription, $this->uow);

        $this->uow
            ->expects(self::once())
            ->method('add')
            ->with($this->subscription)
        ;

        $this->subscription
            ->expects(self::once())
            ->method('unpause')
            ->with()
        ;

        $this->uow
            ->expects(self::once())
            ->method('commit')
        ;

        $this->uow
            ->expects(self::never())
            ->method('clear')
        ;

        $subscription->unpause();
    }

    public function testUnpauseWithError(): void
    {
        $exception = new \RuntimeException('test');
        $this->expectExceptionObject($exception);

        $subscription = new CommittingSubscription($this->subscription, $this->uow);

        $this->uow
            ->expects(self::once())
            ->method('add')
            ->with($this->subscription)
        ;

        $this->subscription
            ->expects(self::once())
            ->method('unpause')
            ->with()
            ->willThrowException($exception)
        ;

        $this->uow
            ->expects(self::never())
            ->method('commit')
        ;

        $this->uow
            ->expects(self::once())
            ->method('clear')
        ;

        $subscription->unpause();
    }
}

namespace Streak\Infrastructure\Domain\Event\Subscription\CommittingSubscriptionTest;

use Streak\Domain\Event;

abstract class IterableStream implements Event\Stream, \IteratorAggregate
{
}
