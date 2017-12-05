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

    public function testSuccessfullyApplyingEventWithPublicHandlingMethod()
    {
        $this->id
            ->expects($this->once())
            ->method('equals')
            ->willReturn(true)
        ;

        $event = new SourcingTest\EventStubForTestingPublicHandlingMethod($this->id);


        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id);

        $this->assertFalse($sourcing->isEventStubForTestingPublicHandlingMethodApplied());

        $sourcing->replay($event);

        $this->assertTrue($sourcing->isEventStubForTestingPublicHandlingMethodApplied());
        $this->assertSame($event, $sourcing->lastReplayed());
        $this->assertEmpty($sourcing->events());
    }

    public function testSuccessfullyApplyingEventWithNonPublicHandlingMethod()
    {
        $this->id
            ->expects($this->once())
            ->method('equals')
            ->willReturn(true)
        ;

        $event = new SourcingTest\EventStubForTestingNonPublicHandlingMethod($this->id);
        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id);

        $this->assertFalse($sourcing->isEventStubForTestingNonPublicHandlingMethodApplied());

        $sourcing->replay($event);

        $this->assertTrue($sourcing->isEventStubForTestingNonPublicHandlingMethodApplied());
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

        $event = new SourcingTest\EventStubForTestingMismatching($this->id);
        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id);

        $exception = new Domain\Exception\EventAndConsumerMismatch($sourcing, $event);
        $this->expectExceptionObject($exception);

        $sourcing->replay($event);
    }

    public function testAggregateWithMissingHandlingMethodForGivenEvent()
    {
        $this->id
            ->expects($this->once())
            ->method('equals')
            ->willReturn(true)
        ;

        $event = new SourcingTest\EventStubForTestingMissingHandlingMethod($this->id);
        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id);

        $exception = new Domain\Event\Exception\NoEventApplyingMethodFound($sourcing, $event);
        $this->expectExceptionObject($exception);

        $sourcing->replay($event);
    }

    public function testAggregateWithTwoOrMoreHandlingMethodsPresentForGivenEvent()
    {
        $this->id
            ->expects($this->once())
            ->method('equals')
            ->willReturn(true)
        ;

        $event = new SourcingTest\EventStubForTestingTwoOrMoreHandlingMethodsPresent($this->id);
        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id);

        $exception = new Domain\Event\Exception\TooManyEventApplyingMethodsFound($sourcing, $event);
        $this->expectExceptionObject($exception);

        $sourcing->replay($event);
    }

    public function testAggregateWithTwoOrMoreParametersPresentOnHandlingMethod()
    {
        $this->id
            ->expects($this->once())
            ->method('equals')
            ->willReturn(true)
        ;

        $event = new SourcingTest\EventStubForTestingTwoOrMoreParametersPresentOnHandlingMethod($this->id);
        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id);

        $exception = new Domain\Event\Exception\NoEventApplyingMethodFound($sourcing, $event);
        $this->expectExceptionObject($exception);

        $sourcing->replay($event);
    }

    public function testAggregateWithOptionalParameterOnHandlingMethodForGivenEvent()
    {
        $this->id
            ->expects($this->once())
            ->method('equals')
            ->willReturn(true)
        ;

        $event = new SourcingTest\EventStubForTestingOptionalParameterOnHandlingMethod($this->id);
        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id);

        $exception = new Domain\Event\Exception\NoEventApplyingMethodFound($sourcing, $event);
        $this->expectExceptionObject($exception);

        $sourcing->replay($event);
    }

    public function testApplyingMethodPresentForEventsParentClassOnly()
    {
        $this->id
            ->expects($this->exactly(2))
            ->method('equals')
            ->willReturn(true)
        ;

        $event1 = new SourcingTest\EventWhichIsSubclassOfEvent7($this->id);
        $event2 = new SourcingTest\AnotherEventWhichIsSubclassOfEvent7($this->id);

        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id);

        $this->assertEquals(0, $sourcing->numberOfAppliesOfEvent7());

        $sourcing->replay($event1, $event2);

        $this->assertEquals(2, $sourcing->numberOfAppliesOfEvent7());
        $this->assertSame($event2, $sourcing->lastReplayed());
        $this->assertEmpty($sourcing->events());
    }

//    public function testApplyingMethodPresentForEventsParentClassAndOnlyOneOfChildren()
//    {
//        $this->id
//            ->expects($this->exactly(2))
//            ->method('equals')
//            ->willReturn(true)
//        ;
//
//        $event1 = new SourcingTest\EventWhichIsSubclassOfEvent8($this->id);
//        $event2 = new SourcingTest\AnotherEventWhichIsSubclassOfEvent8($this->id);
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

    public function testApplyingEventViaCommand()
    {
        $this->id
            ->expects($this->exactly(1))
            ->method('equals')
            ->willReturn(true)
        ;

        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id);

        $this->assertFalse($sourcing->isEvent9Applied());

        $sourcing->command($this->id);

        $this->assertTrue($sourcing->isEvent9Applied());

        $event = new SourcingTest\EventStubForTestingApplyingViaCommand($this->id);

        $this->assertNull($sourcing->lastReplayed());
        $this->assertEquals([$event], $sourcing->events());
    }

    public function testEventSourcingNonConsumer()
    {
        $sourcing = new Domain\Event\SourcingTest\EventSourcedNonConsumer($this->id);

        $exception = new Domain\Event\Exception\SourcingObjectWithEventFailed($sourcing, $this->event);
        $this->expectExceptionObject($exception);

        $sourcing->replay($this->event);
    }
}

namespace Streak\Domain\Event\SourcingTest;

use Streak\Domain;
use Streak\Domain\AggregateRoot;
use Streak\Domain\Event;

class EventSourcedAggregateRootStub implements Event\Consumer
{
    use Event\Sourcing;

    private $id;

    private $eventStubForTestingPublicHandlingMethodApplied = false;
    private $eventStubForTestingNonPublicHandlingMethodApplied = false;
    private $numberOfAppliesOfEvent7 = 0;
    private $event8Applied = false;
    private $event8aApplied = false;
    private $event9Applied = false;

    public function __construct(AggregateRoot\Id $id)
    {
        $this->id = $id;
    }

    public function applyEventStubForTestingPublicHandlingMethodApplied(EventStubForTestingPublicHandlingMethod $event)
    {
        $this->eventStubForTestingPublicHandlingMethodApplied = true;
    }

    private function applyEventStubForTestingNonPublicHandlingMethodApplied(EventStubForTestingNonPublicHandlingMethod $event)
    {
        $this->eventStubForTestingNonPublicHandlingMethodApplied = true;
    }

    public function applyEvent2(EventStubForTestingTwoOrMoreHandlingMethodsPresent $event2)
    {

    }

    public function applyEvent2Deux(EventStubForTestingTwoOrMoreHandlingMethodsPresent $event2)
    {

    }

    public function applyEvent4(EventStubForTestingTwoOrMoreParametersPresentOnHandlingMethod $event, mixed $thisParameterUnneeded)
    {}

    public function applyEvent5(EventStubForTestingOptionalParameterOnHandlingMethod $optionalEventIsInvalid = null)
    {}

    private function applyEvent7(Event7 $event)
    {
        $this->numberOfAppliesOfEvent7++;
    }

    private function applyEvent8(Event8 $event)
    {
        $this->event8Applied = true;
    }

    private function applyEvent8a(EventWhichIsSubclassOfEvent8 $event)
    {
        $this->event8aApplied = true;
    }

    private function applyEvent9(EventStubForTestingApplyingViaCommand $event)
    {
        $this->event9Applied = true;
    }

    private function applyNonEvent(\stdClass $parameter)
    {
    }

    public function command(AggregateRoot\Id $id)
    {
        $this->applyEvent(new EventStubForTestingApplyingViaCommand($id));
    }

    public function isEventStubForTestingPublicHandlingMethodApplied() : bool
    {
        return $this->eventStubForTestingPublicHandlingMethodApplied;
    }

    public function isEventStubForTestingNonPublicHandlingMethodApplied() : bool
    {
        return $this->eventStubForTestingNonPublicHandlingMethodApplied;
    }

    public function isEvent8Applied() : bool
    {
        return $this->event8Applied;
    }

    public function isEvent8aApplied() : bool
    {
        return $this->event8aApplied;
    }

    public function isEvent9Applied() : bool
    {
        return $this->event9Applied;
    }

    public function numberOfAppliesOfEvent7() : bool
    {
        return $this->numberOfAppliesOfEvent7;
    }

    public function id() : Domain\Id
    {
        return $this->id;
    }
}

class EventStubForTestingPublicHandlingMethod implements Domain\Event
{
    private $id;

    public function __construct(AggregateRoot\Id $id)
    {
        $this->id = $id;
    }

    public function producerId() : Domain\Id
    {
        return $this->id;
    }
}

class EventStubForTestingMismatching implements Domain\Event
{
    private $id;

    public function __construct(AggregateRoot\Id $id)
    {
        $this->id = $id;
    }

    public function producerId() : Domain\Id
    {
        return $this->id;
    }
}

class EventStubForTestingTwoOrMoreHandlingMethodsPresent implements Domain\Event
{
    private $id;

    public function __construct(AggregateRoot\Id $id)
    {
        $this->id = $id;
    }

    public function producerId() : Domain\Id
    {
        return $this->id;
    }
}

class EventStubForTestingMissingHandlingMethod implements Domain\Event
{
    private $id;

    public function __construct(AggregateRoot\Id $id)
    {
        $this->id = $id;
    }

    public function producerId() : Domain\Id
    {
        return $this->id;
    }
}

class EventStubForTestingTwoOrMoreParametersPresentOnHandlingMethod implements Domain\Event
{
    private $id;

    public function __construct(AggregateRoot\Id $id)
    {
        $this->id = $id;
    }

    public function producerId() : Domain\Id
    {
        return $this->id;
    }
}

class EventStubForTestingOptionalParameterOnHandlingMethod implements Domain\Event
{
    private $id;

    public function __construct(AggregateRoot\Id $id)
    {
        $this->id = $id;
    }

    public function producerId() : Domain\Id
    {
        return $this->id;
    }
}

class EventStubForTestingNonPublicHandlingMethod implements Domain\Event
{
    private $id;

    public function __construct(AggregateRoot\Id $id)
    {
        $this->id = $id;
    }

    public function producerId() : Domain\Id
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

    public function producerId() : Domain\Id
    {
        return $this->id;
    }
}
class EventWhichIsSubclassOfEvent7 extends Event7 {}
class AnotherEventWhichIsSubclassOfEvent7 extends Event7 {}

class Event8 implements Domain\Event
{
    private $id;

    public function __construct(AggregateRoot\Id $id)
    {
        $this->id = $id;
    }

    public function producerId() : Domain\Id
    {
        return $this->id;
    }
}
class EventWhichIsSubclassOfEvent8 extends Event8 {}
class AnotherEventWhichIsSubclassOfEvent8 extends Event8 {}

class EventStubForTestingApplyingViaCommand implements Domain\Event
{
    private $id;

    public function __construct(AggregateRoot\Id $id)
    {
        $this->id = $id;
    }

    public function producerId() : Domain\Id
    {
        return $this->id;
    }
}

class EventSourcedNonConsumer
{
    use Event\Sourcing;

    private $id;

    public function __construct(AggregateRoot\Id $id)
    {
        $this->id = $id;
    }

    public function id() : Domain\Id
    {
        return $this->id;
    }
}
