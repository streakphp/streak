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

use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Subscription;
use Streak\Domain\EventStore;
use Streak\Domain\Id\UUID;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Event\Subscription\LazyLoadedSubscription
 */
class LazyLoadedSubscriptionTest extends TestCase
{
    /**
     * @var Listener\Id|\PHPUnit\Framework\MockObject\MockObject
     */
    private $id;

    /**
     * @var Listener|\PHPUnit\Framework\MockObject\MockObject
     */
    private $listener;

    /**
     * @var Subscription|\PHPUnit\Framework\MockObject\MockObject
     */
    private $subscription;

    /**
     * @var Subscription\Repository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $repository;

    /**
     * @var Event|\PHPUnit\Framework\MockObject\MockObject
     */
    private $event1;

    /**
     * @var Event|\PHPUnit\Framework\MockObject\MockObject
     */
    private $event2;

    /**
     * @var Event|\PHPUnit\Framework\MockObject\MockObject
     */
    private $event3;

    /**
     * @var EventStore|\PHPUnit\Framework\MockObject\MockObject
     */
    private $store;

    protected function setUp()
    {
        $this->id = $this->getMockBuilder(Listener\Id::class)->getMockForAbstractClass();
        $this->listener = $this->getMockBuilder(Listener::class)->getMockForAbstractClass();
        $this->subscription = $this->getMockBuilder(Subscription::class)->getMockForAbstractClass();
        $this->repository = $this->getMockBuilder(Subscription\Repository::class)->getMockForAbstractClass();
        $this->event1 = $this->getMockBuilder(Event::class)->setMockClassName('event1')->getMockForAbstractClass();
        $this->event1 = Event\Envelope::new($this->event1, UUID::random());
        $this->event2 = $this->getMockBuilder(Event::class)->setMockClassName('event2')->getMockForAbstractClass();
        $this->event2 = Event\Envelope::new($this->event2, UUID::random());
        $this->event3 = $this->getMockBuilder(Event::class)->setMockClassName('event3')->getMockForAbstractClass();
        $this->event3 = Event\Envelope::new($this->event3, UUID::random());
        $this->store = $this->getMockBuilder(EventStore::class)->getMockForAbstractClass();
    }

    public function testObject()
    {
        $subscription = new LazyLoadedSubscription($this->id, $this->repository);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with($this->id)
            ->willReturn($this->subscription)
        ;

        $this->assertSame($this->id, $subscription->subscriptionId());

        $this->subscription
            ->expects($this->once())
            ->method('listener')
            ->willReturn($this->listener)
        ;

        $this->assertSame($this->listener, $subscription->listener());

        $this->subscription
            ->expects($this->once())
            ->method('completed')
            ->willReturn(false)
        ;

        $this->assertSame(false, $subscription->completed());

        $this->subscription
            ->expects($this->once())
            ->method('started')
            ->willReturn(true)
        ;

        $this->assertSame(true, $subscription->started());

        $this->subscription
            ->expects($this->once())
            ->method('starting')
            ->willReturn(true)
        ;

        $this->assertSame(true, $subscription->starting());

        $this->subscription
            ->expects($this->once())
            ->method('restart')
        ;

        $subscription->restart();

        $this->subscription
            ->expects($this->once())
            ->method('startFor')
            ->with($this->event1)
        ;

        $subscription->startFor($this->event1);

        $this->subscription
            ->expects($this->once())
            ->method('subscribeTo')
            ->with($this->store, 94857623)
            ->willReturn([$this->event2, $this->event3])
        ;

        $result = $subscription->subscribeTo($this->store, 94857623);
        $result = iterator_to_array($result);

        $this->assertSame([$this->event2, $this->event3], $result);

        $this->assertSame($this->subscription, $subscription->subscription());
    }
}
