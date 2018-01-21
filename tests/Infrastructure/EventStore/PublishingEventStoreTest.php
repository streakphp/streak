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

namespace Streak\Infrastructure\EventStore;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\EventBus;
use Streak\Domain\EventStore;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\EventStore\PublishingEventStore
 */
class PublishingEventStoreTest extends TestCase
{
    /**
     * @var EventStore|MockObject
     */
    private $store;

    /**
     * @var EventBus|MockObject
     */
    private $bus;

    /**
     * @var Domain\Id|MockObject
     */
    private $id;

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
     * @var Event\Log|MockObject
     */
    private $log;

    /**
     * @var Event\FilterableStream|MockObject
     */
    private $stream;

    protected function setUp()
    {
        $this->store = $this->getMockBuilder(EventStore::class)->getMockForAbstractClass();
        $this->bus = $this->getMockBuilder(EventBus::class)->getMockForAbstractClass();

        $this->id = $this->getMockBuilder(Domain\Id::class)->getMockForAbstractClass();

        $this->event1 = $this->getMockBuilder(Event::class)->setMockClassName('event1')->getMockForAbstractClass();
        $this->event2 = $this->getMockBuilder(Event::class)->setMockClassName('event2')->getMockForAbstractClass();
        $this->event3 = $this->getMockBuilder(Event::class)->setMockClassName('event3')->getMockForAbstractClass();

        $this->log = $this->getMockBuilder(Event\Log::class)->getMockForAbstractClass();
        $this->stream = $this->getMockBuilder(Event\FilterableStream::class)->getMockForAbstractClass();
    }

    public function testStore()
    {
        $store = new PublishingEventStore($this->store, $this->bus);

        $this->store
            ->expects($this->exactly(3))
            ->method('add')
            ->withConsecutive(
                [$this->id, null, $this->event2],
                [$this->id, $this->event1, $this->event2],
                [$this->id, $this->event1, $this->event2, $this->event3]
            )
        ;

        $this->bus
            ->expects($this->exactly(3))
            ->method('publish')
            ->withConsecutive(
                [$this->event2],
                [$this->event2],
                [$this->event2, $this->event3]
            )
        ;

        $store->add($this->id, null, $this->event2);
        $store->add($this->id, $this->event1, $this->event2);
        $store->add($this->id, $this->event1, $this->event2, $this->event3);

        $this->store
            ->expects($this->once())
            ->method('stream')
            ->with($this->id)
            ->willReturn($this->stream)
        ;

        $this->assertSame($this->stream, $store->stream($this->id));

        $this->store
            ->expects($this->once())
            ->method('log')
            ->with()
            ->willReturn($this->log)
        ;

        $this->assertSame($this->log, $store->log());
    }
}
