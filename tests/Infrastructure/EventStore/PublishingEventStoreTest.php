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
use Streak\Domain\Id\UUID;

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
     * @var EventStore|Schemable|MockObject
     */
    private $schemableStore;

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
     * @var Event\Stream|MockObject
     */
    private $stream1;

    /**
     * @var Event\Stream|MockObject
     */
    private $stream2;

    /**
     * @var Schema|MockObject
     */
    private $schema;

    protected function setUp()
    {
        $this->store = $this->getMockBuilder(EventStore::class)->getMockForAbstractClass();
        $this->schemableStore = $this->getMockBuilder([EventStore::class, Schemable::class])->getMock();
        $this->bus = $this->getMockBuilder(EventBus::class)->getMockForAbstractClass();

        $this->id = $this->getMockBuilder(Domain\Id::class)->getMockForAbstractClass();

        $this->event1 = $this->getMockBuilder(Event::class)->setMockClassName('event1')->getMockForAbstractClass();
        $this->event1 = Event\Envelope::new($this->event1, $this->id, 1);
        $this->event2 = $this->getMockBuilder(Event::class)->setMockClassName('event2')->getMockForAbstractClass();
        $this->event2 = Event\Envelope::new($this->event2, $this->id, 2);
        $this->event3 = $this->getMockBuilder(Event::class)->setMockClassName('event3')->getMockForAbstractClass();
        $this->event3 = Event\Envelope::new($this->event3, $this->id, 3);

        $this->stream1 = $this->getMockBuilder(Event\Stream::class)->getMockForAbstractClass();
        $this->stream2 = $this->getMockBuilder(Event\Stream::class)->getMockForAbstractClass();
        $this->schema = $this->getMockBuilder(Schema::class)->getMockForAbstractClass();
    }

    public function testStore()
    {
        $store = new PublishingEventStore($this->store, $this->bus);

        $this->store
            ->expects($this->exactly(3))
            ->method('add')
            ->withConsecutive(
                [$this->event2],
                [$this->event2],
                [$this->event2, $this->event3]
            )
            ->willReturnOnConsecutiveCalls(
                [$this->event2],
                [$this->event2],
                [$this->event2, $this->event3]
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

        $store->add($this->event2);
        $store->add($this->event2);
        $store->add($this->event2, $this->event3);
    }

    public function testStoringNoEvents()
    {
        $store = new PublishingEventStore($this->store, $this->bus);

        $this->store
            ->expects($this->never())
            ->method('add')
        ;

        $this->bus
            ->expects($this->never())
            ->method('publish')
        ;

        $events = [];
        $store->add(...$events);
        $store->add(...$events);
    }

    public function testSchemalessEventStore()
    {
        $store = new PublishingEventStore($this->store, $this->bus);

        $this->store
            ->expects($this->never())
            ->method($this->anything())
        ;

        $schema = $store->schema();

        $this->assertNull($schema);
    }

    public function testSchemableEventStore()
    {
        $store = new PublishingEventStore($this->schemableStore, $this->bus);

        $this->schemableStore
            ->expects($this->once())
            ->method('schema')
            ->with()
            ->willReturn($this->schema)
        ;

        $schema = $store->schema();

        $this->assertSame($this->schema, $schema);
    }

    public function testRetrievingStream()
    {
        $store = new PublishingEventStore($this->store, $this->bus);
        $filter = EventStore\Filter::nothing();

        $this->store
            ->expects($this->exactly(2))
            ->method('stream')
            ->withConsecutive(
                [null],
                [$filter]
            )
            ->willReturn(
                $this->stream1,
                $this->stream2
            )
        ;

        $stream = $store->stream();

        $this->assertSame($this->stream1, $stream);

        $stream = $store->stream($filter);

        $this->assertSame($this->stream2, $stream);
    }

    public function testItReturnsEvent() : void
    {
        $store = new PublishingEventStore($this->store, $this->bus);

        $uuid1 = new UUID('563dccb6-f225-4efb-8cc5-fc340163d3ef');
        $uuid2 = new UUID('abd8a704-dba3-4919-bc3a-5cc16faaac0a');

        $this->store
            ->expects($this->exactly(2))
            ->method('event')
            ->withConsecutive([$uuid1], [$uuid2])
            ->willReturnOnConsecutiveCalls($this->event1, null)
        ;
        $this->assertSame($this->event1, $store->event($uuid1));
        $this->assertNull($store->event($uuid2));
    }
}
