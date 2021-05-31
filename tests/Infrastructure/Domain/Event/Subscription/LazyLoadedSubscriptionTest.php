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

use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Application\Event\Listener;
use Streak\Application\Event\Listener\Subscription;
use Streak\Domain\EventStore;
use Streak\Domain\Id\UUID;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\Event\Subscription\LazyLoadedSubscription
 */
class LazyLoadedSubscriptionTest extends TestCase
{
    private Listener\Id $id;

    private Listener $listener;

    private Subscription $subscription;

    private Subscription\Repository $repository;

    private Event\Envelope $event1;
    private Event\Envelope $event2;
    private Event\Envelope $event3;

    private EventStore $store;

    protected function setUp(): void
    {
        $this->id = $this->getMockBuilder(Listener\Id::class)->getMockForAbstractClass();
        $this->listener = $this->getMockBuilder(Listener::class)->getMockForAbstractClass();
        $this->subscription = $this->getMockBuilder(Subscription::class)->getMockForAbstractClass();
        $this->repository = $this->getMockBuilder(Subscription\Repository::class)->getMockForAbstractClass();
        $this->event1 = Event\Envelope::new($this->getMockBuilder(Event::class)->setMockClassName('event1')->getMockForAbstractClass(), UUID::random());
        $this->event2 = Event\Envelope::new($this->getMockBuilder(Event::class)->setMockClassName('event2')->getMockForAbstractClass(), UUID::random());
        $this->event3 = Event\Envelope::new($this->getMockBuilder(Event::class)->setMockClassName('event3')->getMockForAbstractClass(), UUID::random());
        $this->store = $this->getMockBuilder(EventStore::class)->getMockForAbstractClass();
    }

    public function testObject(): void
    {
        $subscription = new LazyLoadedSubscription($this->id, $this->repository);

        $this->repository
            ->expects(self::once())
            ->method('find')
            ->with($this->id)
            ->willReturn($this->subscription)
        ;

        self::assertSame($this->id, $subscription->subscriptionId());

        $this->subscription
            ->expects(self::once())
            ->method('listener')
            ->willReturn($this->listener)
        ;

        self::assertSame($this->listener, $subscription->listener());

        $this->subscription
            ->expects(self::exactly(2))
            ->method('version')
            ->with()
            ->willReturnOnConsecutiveCalls(1000, 1001)
        ;

        self::assertSame(1000, $subscription->version());
        self::assertSame(1001, $subscription->version());

        $this->subscription
            ->expects(self::once())
            ->method('completed')
            ->willReturn(false)
        ;

        self::assertFalse($subscription->completed());

        $this->subscription
            ->expects(self::once())
            ->method('started')
            ->willReturn(true)
        ;

        self::assertTrue($subscription->started());

        $this->subscription
            ->expects(self::once())
            ->method('starting')
            ->willReturn(true)
        ;

        self::assertTrue($subscription->starting());

        $this->subscription
            ->expects(self::once())
            ->method('restart')
        ;

        $subscription->restart();

        $this->subscription
            ->expects(self::once())
            ->method('startFor')
            ->with($this->event1)
        ;

        $subscription->startFor($this->event1);

        $this->subscription
            ->expects(self::once())
            ->method('subscribeTo')
            ->with($this->store, 94_857_623)
            ->willReturn([$this->event2, $this->event3])
        ;

        $result = $subscription->subscribeTo($this->store, 94_857_623);
        $result = iterator_to_array($result);

        self::assertSame([$this->event2, $this->event3], $result);

        self::assertSame($this->subscription, $subscription->subscription());

        $this->subscription
            ->expects(self::exactly(2))
            ->method('paused')
            ->with()
            ->willReturnOnConsecutiveCalls(false, true)
        ;

        self::assertFalse($subscription->paused());
        self::assertTrue($subscription->paused());

        $this->subscription
            ->expects(self::once())
            ->method('pause')
            ->with()
        ;

        $subscription->pause();

        $this->subscription
            ->expects(self::once())
            ->method('unpause')
            ->with()
        ;

        $subscription->unpause();
    }
}
