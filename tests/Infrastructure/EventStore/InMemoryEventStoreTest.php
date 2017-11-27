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
        $this->id1
            ->expects($this->atLeastOnce())
            ->method('toString')
            ->willReturn('id1');

        $this->id2
            ->expects($this->atLeastOnce())
            ->method('toString')
            ->willReturn('id2');

        $store = new InMemoryEventStore();

        $this->assertEmpty($store->all());
        $this->assertEmpty($store->find($this->id1));
        $this->assertEmpty($store->find($this->id2));

        $event1 = new EventStub($this->id1);
        $event2 = new EventStub($this->id1);
        $event3 = new EventStub($this->id2);
        $event4 = new EventStub($this->id2);

        $store->add($event1, $event2);

        $this->assertEquals([$event1, $event2], $store->find($this->id1));
        $this->assertEquals([], $store->find($this->id2));
        $this->assertEquals([$event1, $event2], $store->all());

        $store->add($event3, $event4);

        $this->assertEquals([$event1, $event2], $store->find($this->id1));
        $this->assertEquals([$event3, $event4], $store->find($this->id2));
        $this->assertEquals([$event1, $event2, $event3, $event4], $store->all());

        $store->clear();

        $this->assertEquals([], $store->find($this->id1));
        $this->assertEquals([], $store->find($this->id2));
        $this->assertEmpty($store->all());
    }

    public function testWrongAggregate()
    {
        $store = new InMemoryEventStore();

        $event1 = new EventStub($this->id1);
        $event2 = new EventStub($this->id1);
        $event3 = new EventStub($this->id2);
        $event4 = new EventStub($this->id2);

        $this->id1
            ->expects($this->atLeastOnce())
            ->method('toString')
            ->willReturn('');

        $exception = new Domain\Exception\InvalidAggregateIdGiven($this->id1);
        $this->expectExceptionObject($exception);

        $store->add($event1, $event2, $event3, $event4);
    }
}

class EventStub implements Domain\Event
{
    private $aggregateId;

    public function __construct(Domain\AggregateRootId $aggregateId)
    {
        $this->aggregateId = $aggregateId;
    }

    public function aggregateRootId() : Domain\AggregateRootId
    {
        return $this->aggregateId;
    }
}

class EventSourcedAggregateRootStub extends Domain\EventSourced\AggregateRoot
{
}
