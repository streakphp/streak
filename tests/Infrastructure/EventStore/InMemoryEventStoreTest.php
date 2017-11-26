<?php

namespace Streak\Infrastructure\EventStore;

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;
use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class InMemoryEventStoreTest extends TestCase
{

    /**
     * @var Domain\AggregateRootId|\PHPUnit_Framework_MockObject_MockObject
     */
    private $id1;

    /**
     * @var Domain\AggregateRootId|\PHPUnit_Framework_MockObject_MockObject
     */
    private $id2;

    public function setUp()
    {
        $this->id1 = $this->getMockBuilder(Domain\AggregateRootId::class)->getMockForAbstractClass();
        $this->id2 = $this->getMockBuilder(Domain\AggregateRootId::class)->getMockForAbstractClass();
    }

    public function testStorage()
    {
        $store = new InMemoryEventStore();

        $event1 = $this->getMockBuilder(Domain\Event::class)->setMockClassName('event1')->getMockForAbstractClass();
        $event2 = $this->getMockBuilder(Domain\Event::class)->setMockClassName('event2')->getMockForAbstractClass();
        $event3 = $this->getMockBuilder(Domain\Event::class)->setMockClassName('event3')->getMockForAbstractClass();
        $event4 = $this->getMockBuilder(Domain\Event::class)->setMockClassName('event4')->getMockForAbstractClass();

        $this->id1
            ->expects($this->atLeastOnce())
            ->method('toString')
            ->willReturn('id1');

        $this->id2
            ->expects($this->atLeastOnce())
            ->method('toString')
            ->willReturn('id2');

        $aggregate1 = new EventSourcedAggregateRootStub($this->id1);
        $aggregate2 = new EventSourcedAggregateRootStub($this->id2);

        $store->addEvents($aggregate1, $event1, $event2);

        $this->assertEquals([$event1, $event2], $store->getEvents($aggregate1));
        $this->assertEquals([], $store->getEvents($aggregate2));

        $store->addEvents($aggregate2, $event3, $event4);

        $this->assertEquals([$event1, $event2], $store->getEvents($aggregate1));
        $this->assertEquals([$event3, $event4], $store->getEvents($aggregate2));

        $store->clear();

        $this->assertEquals([], $store->getEvents($aggregate1));
        $this->assertEquals([], $store->getEvents($aggregate2));
    }

    public function testWrongAggregate()
    {
        $store = new InMemoryEventStore();

        $event1 = $this->getMockBuilder(Domain\Event::class)->setMockClassName('event1')->getMockForAbstractClass();
        $event2 = $this->getMockBuilder(Domain\Event::class)->setMockClassName('event2')->getMockForAbstractClass();
        $event3 = $this->getMockBuilder(Domain\Event::class)->setMockClassName('event3')->getMockForAbstractClass();
        $event4 = $this->getMockBuilder(Domain\Event::class)->setMockClassName('event4')->getMockForAbstractClass();

        $this->id1
            ->expects($this->atLeastOnce())
            ->method('toString')
            ->willReturn('');

        $aggregate1 = new EventSourcedAggregateRootStub($this->id1);

        $exception = new Domain\Exception\InvalidAggregateGiven($aggregate1);
        $this->expectExceptionObject($exception);

        $store->addEvents($aggregate1, $event1, $event2, $event3, $event4);
    }
}

class EventSourcedAggregateRootStub extends Domain\EventSourced\AggregateRoot
{
}
