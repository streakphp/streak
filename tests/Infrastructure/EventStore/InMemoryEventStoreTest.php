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
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\EventStore\InMemoryEventStore
 */
class InMemoryEventStoreTest extends TestCase
{
    /**
     * @var Domain\AggregateRoot\Id|\PHPUnit_Framework_MockObject_MockObject
     */
    private $aggregateRootId1;

    /**
     * @var Domain\AggregateRoot\Id|\PHPUnit_Framework_MockObject_MockObject
     */
    private $aggregaterootId2;

    /**
     * @var Domain\Event|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event1;

    /**
     * @var Domain\Event|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event2;

    /**
     * @var Domain\Event|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event3;

    /**
     * @var Domain\Event|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event4;

    public function setUp()
    {
        $this->aggregateRootId1 = $this->getMockBuilder(Domain\AggregateRoot\Id::class)->getMockForAbstractClass();
        $this->aggregaterootId2 = $this->getMockBuilder(Domain\AggregateRoot\Id::class)->getMockForAbstractClass();

        $this->event1 = $this->getMockBuilder(Domain\Event::class)->getMockForAbstractClass();
        $this->event2 = $this->getMockBuilder(Domain\Event::class)->getMockForAbstractClass();
        $this->event3 = $this->getMockBuilder(Domain\Event::class)->getMockForAbstractClass();
        $this->event4 = $this->getMockBuilder(Domain\Event::class)->getMockForAbstractClass();
    }

    public function testStorage()
    {
        $this->event1
            ->expects($this->atLeastOnce())
            ->method('aggregateRootId')
            ->with()
            ->willReturn($this->aggregateRootId1)
        ;

        $this->event2
            ->expects($this->atLeastOnce())
            ->method('aggregateRootId')
            ->with()
            ->willReturn($this->aggregateRootId1)
        ;

        $this->event3
            ->expects($this->atLeastOnce())
            ->method('aggregateRootId')
            ->with()
            ->willReturn($this->aggregaterootId2)
        ;

        $this->event4
            ->expects($this->atLeastOnce())
            ->method('aggregateRootId')
            ->with()
            ->willReturn($this->aggregaterootId2)
        ;

        $this->aggregateRootId1
            ->expects($this->atLeastOnce())
            ->method('toString')
            ->willReturn('id1')
        ;

        $this->aggregaterootId2
            ->expects($this->atLeastOnce())
            ->method('toString')
            ->willReturn('id2')
        ;

        $store = new InMemoryEventStore();

        $this->assertEmpty($store->all());
        $this->assertEmpty($store->find($this->aggregateRootId1));
        $this->assertEmpty($store->find($this->aggregaterootId2));


        $store->add($this->event1, $this->event2);

        $this->assertEquals([$this->event1, $this->event2], $store->find($this->aggregateRootId1));
        $this->assertEquals([], $store->find($this->aggregaterootId2));
        $this->assertEquals([$this->event1, $this->event2], $store->all());

        $store->add($this->event3, $this->event4);

        $this->assertEquals([$this->event1, $this->event2], $store->find($this->aggregateRootId1));
        $this->assertEquals([$this->event3, $this->event4], $store->find($this->aggregaterootId2));
        $this->assertEquals([$this->event1, $this->event2, $this->event3, $this->event4], $store->all());

        $store->clear();

        $this->assertEquals([], $store->find($this->aggregateRootId1));
        $this->assertEquals([], $store->find($this->aggregaterootId2));
        $this->assertEmpty($store->all());
    }

    public function testWrongAggregate()
    {
        $this->event1
            ->expects($this->once())
            ->method('aggregateRootId')
            ->with()
            ->willReturn($this->aggregateRootId1)
        ;

        $this->aggregateRootId1
            ->expects($this->atLeastOnce())
            ->method('toString')
            ->willReturn('')
        ;

        $exception = new Domain\Exception\InvalidAggregateIdGiven($this->aggregateRootId1);
        $this->expectExceptionObject($exception);

        $store = new InMemoryEventStore();
        $store->add($this->event1, $this->event2, $this->event3, $this->event4);
    }
}
