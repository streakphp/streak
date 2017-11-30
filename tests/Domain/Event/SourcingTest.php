<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain\Event;

use PHPUnit\Framework\TestCase;
use Streak\Domain;
use Streak\Domain\AggregateRoot;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Sourcing
 */
class SourcingTest extends TestCase
{
    /**
     * @var AggregateRoot\Id|\PHPUnit_Framework_MockObject_MockObject
     */
    private $id;

    /**
     * @var Domain\Event|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event;

    public function setUp()
    {
        $this->id = $this->getMockBuilder(AggregateRoot\Id::class)->getMockForAbstractClass();
        $this->event = $this->getMockBuilder(Domain\Event::class)->getMockForAbstractClass();
    }

    public function testSuccessfullyApplyingEvent()
    {
        $this->id
            ->expects($this->once())
            ->method('equals')
            ->willReturn(true)
        ;

        $event = new SourcingTest\Event1($this->id);


        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id);

        $this->assertFalse($sourcing->isEvent1Applied());

        $sourcing->replay($event);

        $this->assertTrue($sourcing->isEvent1Applied());
        $this->assertSame($event, $sourcing->lastReplayed());
        $this->assertEmpty($sourcing->events());
    }

    public function testMismatchedEvent()
    {
        $this->id
            ->expects($this->once())
            ->method('equals')
            ->willReturn(false)
        ;

        $event = new SourcingTest\Event1($this->id);
        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id);

        $exception = new Domain\Exception\EventAndConsumerMismatch($sourcing, $event);
        $this->expectExceptionObject($exception);

        $sourcing->replay($event);
    }

    public function testTwoOrMoreMethodsPresentForSingleEvent()
    {
        $this->id
            ->expects($this->once())
            ->method('equals')
            ->willReturn(true)
        ;

        $event = new SourcingTest\Event2($this->id);
        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id);

        $exception = new Domain\Event\Exception\SourcingObjectWithEventFailed($sourcing, $event);
        $this->expectExceptionObject($exception);

        $sourcing->replay($event);
    }

    public function testNoMethodsPresentForAnEvent()
    {
        $this->id
            ->expects($this->once())
            ->method('equals')
            ->willReturn(true)
        ;

        $event = new SourcingTest\Event3($this->id);
        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id);

        $exception = new Domain\Event\Exception\SourcingObjectWithEventFailed($sourcing, $event);
        $this->expectExceptionObject($exception);

        $sourcing->replay($event);
    }

    public function testApplyingMethodPresentButHavingMoreThanOneParameter()
    {
        $this->id
            ->expects($this->once())
            ->method('equals')
            ->willReturn(true)
        ;

        $event = new SourcingTest\Event4($this->id);
        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id);

        $exception = new Domain\Event\Exception\SourcingObjectWithEventFailed($sourcing, $event);
        $this->expectExceptionObject($exception);

        $sourcing->replay($event);
    }

    public function testApplyingMethodPresentButHavingEventParameterOptional()
    {
        $this->id
            ->expects($this->once())
            ->method('equals')
            ->willReturn(true)
        ;

        $event = new SourcingTest\Event5($this->id);
        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id);

        $exception = new Domain\Event\Exception\SourcingObjectWithEventFailed($sourcing, $event);
        $this->expectExceptionObject($exception);

        $sourcing->replay($event);
    }

    public function testApplyingMethodPresentButNonPublic()
    {
        $this->id
            ->expects($this->once())
            ->method('equals')
            ->willReturn(true)
        ;

        $event = new SourcingTest\Event6($this->id);
        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id);

        $this->assertFalse($sourcing->isEvent6Applied());

        $sourcing->replay($event);

        $this->assertTrue($sourcing->isEvent6Applied());
        $this->assertSame($event, $sourcing->lastReplayed());
        $this->assertEmpty($sourcing->events());
    }

    public function testApplyingMethodPresentForEventsParentClassOnly()
    {
        $this->id
            ->expects($this->exactly(2))
            ->method('equals')
            ->willReturn(true)
        ;

        $event1 = new SourcingTest\Event7a($this->id);
        $event2 = new SourcingTest\Event7b($this->id);

        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id);

        $this->assertEquals(0, $sourcing->numberOfAppliesOfEvent7());

        $sourcing->replay($event1, $event2);

        $this->assertEquals(2, $sourcing->numberOfAppliesOfEvent7());
        $this->assertSame($event2, $sourcing->lastReplayed());
        $this->assertEmpty($sourcing->events());
    }
//
//    public function testApplyingMethodPresentForEventsParentClassAndOnlyOneOfChildren()
//    {
//        $this->id
//            ->expects($this->exactly(2))
//            ->method('equals')
//            ->willReturn(true)
//        ;
//
//        $event1 = new SourcingTest\Event8a($this->id);
//        $event2 = new SourcingTest\Event8b($this->id);
//
//        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id);
//
//        $this->assertFalse($sourcing->isEvent8Applied());
//        $this->assertFalse($sourcing->isEvent8aApplied());
//
//        $sourcing->replay($event1, $event2);
//
//        $this->assertTrue($sourcing->isEvent8Applied());
//        $this->assertTrue($sourcing->isEvent8aApplied());
//        $this->assertSame($event2, $sourcing->lastReplayed());
//        $this->assertEmpty($sourcing->events());
//    }
}

namespace Streak\Domain\Event\SourcingTest;

use Streak\Domain;
use Streak\Domain\AggregateRoot;
use Streak\Domain\Event;

class EventSourcedAggregateRootStub implements Event\Consumer
{
    use Event\Sourcing;

    private $id;

    private $event1Applied = false;
    private $event6Applied = false;
    private $numberOfAppliesOfEvent7 = 0;
    private $event8Applied = false;
    private $event8aApplied = false;

    public function __construct(AggregateRoot\Id $id)
    {
        $this->id = $id;
    }

    public function applyEvent1(Event1 $event)
    {
        $this->event1Applied = true;
    }

    public function applyEvent2(Event2 $event2)
    {

    }

    public function applyEvent2Deux(Event2 $event2)
    {

    }

    public function applyEvent4(Event4 $event, mixed $thisParameterUnneeded)
    {}

    public function applyEvent5(Event5 $optionalEventIsInvalid = null)
    {}

    private function applyEvent6(Event6 $event)
    {
        $this->event6Applied = true;
    }

    private function applyEvent7(Event7 $event)
    {
        $this->numberOfAppliesOfEvent7++;
    }

    private function applyEvent8(Event8 $event)
    {
        $this->event8Applied = true;
    }

    private function applyEvent8a(Event8a $event)
    {
        $this->event8aApplied = true;
    }

    public function isEvent1Applied() : bool
    {
        return $this->event1Applied;
    }

    public function isEvent6Applied() : bool
    {
        return $this->event6Applied;
    }

    public function isEvent8Applied() : bool
    {
        return $this->event8Applied;
    }

    public function isEvent8aApplied() : bool
    {
        return $this->event8aApplied;
    }

    public function numberOfAppliesOfEvent7() : bool
    {
        return $this->numberOfAppliesOfEvent7;
    }

    public function aggregateRootId() : AggregateRoot\Id
    {
        return $this->id;
    }
}

class Event1 implements Domain\Event
{
    private $id;

    public function __construct(AggregateRoot\Id $id)
    {
        $this->id = $id;
    }

    public function aggregateRootId() : AggregateRoot\Id
    {
        return $this->id;
    }
}

class Event2 implements Domain\Event
{
    private $id;

    public function __construct(AggregateRoot\Id $id)
    {
        $this->id = $id;
    }

    public function aggregateRootId() : AggregateRoot\Id
    {
        return $this->id;
    }
}

class Event3 implements Domain\Event
{
    private $id;

    public function __construct(AggregateRoot\Id $id)
    {
        $this->id = $id;
    }

    public function aggregateRootId() : AggregateRoot\Id
    {
        return $this->id;
    }
}

class Event4 implements Domain\Event
{
    private $id;

    public function __construct(AggregateRoot\Id $id)
    {
        $this->id = $id;
    }

    public function aggregateRootId() : AggregateRoot\Id
    {
        return $this->id;
    }
}

class Event5 implements Domain\Event
{
    private $id;

    public function __construct(AggregateRoot\Id $id)
    {
        $this->id = $id;
    }

    public function aggregateRootId() : AggregateRoot\Id
    {
        return $this->id;
    }
}

class Event6 implements Domain\Event
{
    private $id;

    public function __construct(AggregateRoot\Id $id)
    {
        $this->id = $id;
    }

    public function aggregateRootId() : AggregateRoot\Id
    {
        return $this->id;
    }
}

class Event7 implements Domain\Event
{
    private $id;

    public function __construct(AggregateRoot\Id $id)
    {
        $this->id = $id;
    }

    public function aggregateRootId() : AggregateRoot\Id
    {
        return $this->id;
    }
}
class Event7a extends Event7 {}
class Event7b extends Event7 {}


class Event8 implements Domain\Event
{
    private $id;

    public function __construct(AggregateRoot\Id $id)
    {
        $this->id = $id;
    }

    public function aggregateRootId() : AggregateRoot\Id
    {
        return $this->id;
    }
}
class Event8a extends Event8 {}
class Event8b extends Event8 {}
