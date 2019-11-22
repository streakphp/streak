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

namespace Streak\Domain\Event;

use PHPUnit\Framework\TestCase;
use Streak\Domain;
use Streak\Domain\AggregateRoot;
use Streak\Domain\Event;
use Streak\Infrastructure\Event\InMemoryStream;

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
        $this->event = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
    }

    public function testSuccessfullyApplyingEventWithPublicHandlingMethod()
    {
        $event = new SourcingTest\EventStubForTestingPublicHandlingMethod();

        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id);

        $this->assertFalse($sourcing->isEventStubForTestingPublicHandlingMethodApplied());
        $this->assertNull($sourcing->lastReplayed());
        $this->assertNull($sourcing->lastEvent());
        $this->assertEquals(0, $sourcing->version());
        $this->assertEmpty($sourcing->events());

        $sourcing->replay(new InMemoryStream($event));

        $this->assertTrue($sourcing->isEventStubForTestingPublicHandlingMethodApplied());
        $this->assertSame($event, $sourcing->lastReplayed());
        $this->assertEquals($event, $sourcing->lastEvent());
        $this->assertEquals(1, $sourcing->version());
        $this->assertEmpty($sourcing->events());

        $sourcing->commit();

        $this->assertTrue($sourcing->isEventStubForTestingPublicHandlingMethodApplied());
        $this->assertSame($event, $sourcing->lastReplayed());
        $this->assertEquals($event, $sourcing->lastEvent());
        $this->assertEquals(1, $sourcing->version());
        $this->assertEmpty($sourcing->events());
    }

    public function testSuccessfullyApplyingEventWithNonPublicHandlingMethod()
    {
        $event = new SourcingTest\EventStubForTestingNonPublicHandlingMethod();
        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id);

        $this->assertFalse($sourcing->isEventStubForTestingNonPublicHandlingMethodApplied());

        $sourcing->replay(new InMemoryStream($event));

        $this->assertTrue($sourcing->isEventStubForTestingNonPublicHandlingMethodApplied());
        $this->assertSame($event, $sourcing->lastReplayed());
        $this->assertEquals($event, $sourcing->lastEvent());
        $this->assertEquals(1, $sourcing->version());
        $this->assertEmpty($sourcing->events());
    }

    public function testAggregateWithMissingHandlingMethodForGivenEvent()
    {
        $event = new SourcingTest\EventStubForTestingMissingHandlingMethod();
        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id);

        $exception = new Domain\Event\Exception\NoEventApplyingMethodFound($sourcing, $event);
        $this->expectExceptionObject($exception);

        $sourcing->replay(new InMemoryStream($event));
    }

    public function testAggregateWithTwoOrMoreHandlingMethodsPresentForGivenEvent()
    {
        $event = new SourcingTest\EventStubForTestingTwoOrMoreHandlingMethodsPresent();
        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id);

        $exception = new Domain\Event\Exception\TooManyEventApplyingMethodsFound($sourcing, $event);
        $this->expectExceptionObject($exception);

        $sourcing->replay(new InMemoryStream($event));
    }

    public function testAggregateWithTwoOrMoreParametersPresentOnHandlingMethod()
    {
        $event = new SourcingTest\EventStubForTestingTwoOrMoreParametersPresentOnHandlingMethod();
        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id);

        $exception = new Domain\Event\Exception\NoEventApplyingMethodFound($sourcing, $event);
        $this->expectExceptionObject($exception);

        $sourcing->replay(new InMemoryStream($event));
    }

    public function testAggregateWithOptionalParameterOnHandlingMethodForGivenEvent()
    {
        $event = new SourcingTest\EventStubForTestingOptionalParameterOnHandlingMethod();
        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id);

        $exception = new Domain\Event\Exception\NoEventApplyingMethodFound($sourcing, $event);
        $this->expectExceptionObject($exception);

        $sourcing->replay(new InMemoryStream($event));
    }

    public function testApplyingMethodPresentForEventsParentClassOnly()
    {
        $event1 = new SourcingTest\EventWhichIsSubclassOfEvent7();
        $event2 = new SourcingTest\AnotherEventWhichIsSubclassOfEvent7();

        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id);

        $this->assertEquals(0, $sourcing->numberOfAppliesOfEvent7());

        $sourcing->replay(new InMemoryStream($event1, $event2));

        $this->assertEquals(2, $sourcing->numberOfAppliesOfEvent7());
        $this->assertSame($event2, $sourcing->lastReplayed());
        $this->assertEquals($event2, $sourcing->lastEvent());
        $this->assertEquals(2, $sourcing->version());
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
        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id);
        $this->assertEmpty($sourcing->events());
        $this->assertNull($sourcing->lastEvent());

        $this->assertFalse($sourcing->isEvent9Applied());

        $sourcing->command1($this->id);

        $this->assertTrue($sourcing->isEvent9Applied());

        $event = new SourcingTest\EventStubForTestingApplyingViaCommand();

        $this->assertNull($sourcing->lastReplayed());
        $this->assertEquals($event, $sourcing->lastEvent());
        $this->assertEquals(0, $sourcing->version());
        $this->assertEquals([$event], $sourcing->events());

        $sourcing->commit();

        $this->assertNull($sourcing->lastReplayed());
        $this->assertEquals($event, $sourcing->lastEvent());
        $this->assertEquals(1, $sourcing->version());
        $this->assertEquals([], $sourcing->events());
    }

    public function testApplyingEventViaCommandResultingInAnException()
    {
        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id);

        $exception = new \Exception('Command resulting in an exception');
        $this->expectExceptionObject($exception);

        try {
            $this->assertEmpty($sourcing->events());
            $this->assertNull($sourcing->lastEvent());
            $this->assertNull($sourcing->lastReplayed());

            $sourcing->command2($this->id);
        } catch (\Exception $thrown) {
            $this->assertEmpty($sourcing->events());
            $this->assertNull($sourcing->lastEvent());
            $this->assertNull($sourcing->lastReplayed());
        } finally {
            $this->assertTrue(isset($thrown));

            throw $thrown;
        }
    }

    public function testEventSourcingNonConsumer()
    {
        $sourcing = new Domain\Event\SourcingTest\EventSourcedNonConsumer($this->id);

        $exception = new Domain\Event\Exception\SourcingObjectWithEventFailed($sourcing, $this->event);
        $this->expectExceptionObject($exception);

        $sourcing->replay(new InMemoryStream($this->event));
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

    public function producerId() : Domain\Id
    {
        return $this->id;
    }

    public function applyEventStubForTestingPublicHandlingMethodApplied(EventStubForTestingPublicHandlingMethod $event)
    {
        $this->eventStubForTestingPublicHandlingMethodApplied = true;
    }

    public function applyEvent2(EventStubForTestingTwoOrMoreHandlingMethodsPresent $event2)
    {
    }

    public function applyEvent2Deux(EventStubForTestingTwoOrMoreHandlingMethodsPresent $event2)
    {
    }

    public function applyEvent4(EventStubForTestingTwoOrMoreParametersPresentOnHandlingMethod $event, mixed $thisParameterUnneeded)
    {
    }

    public function applyEvent5(EventStubForTestingOptionalParameterOnHandlingMethod $optionalEventIsInvalid = null)
    {
    }

    public function command1(AggregateRoot\Id $id)
    {
        $this->applyEvent(new EventStubForTestingApplyingViaCommand());
    }

    public function command2(AggregateRoot\Id $id)
    {
        $this->applyEvent(new EventStubForTestingApplyingViaCommandResultingInException());
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

    public function numberOfAppliesOfEvent7() : int
    {
        return $this->numberOfAppliesOfEvent7;
    }

    public function id() : Domain\Id
    {
        return $this->id;
    }

    private function applyEventStubForTestingNonPublicHandlingMethodApplied(EventStubForTestingNonPublicHandlingMethod $event)
    {
        $this->eventStubForTestingNonPublicHandlingMethodApplied = true;
    }

    private function applyEvent7(Event7 $event)
    {
        ++$this->numberOfAppliesOfEvent7;
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

    private function applyEvent10(EventStubForTestingApplyingViaCommandResultingInException $event)
    {
        throw new \Exception('Command resulting in an exception');
    }

    private function applyNonEvent(\stdClass $parameter)
    {
    }

    private function applySomethingElse($parameters)
    {
    }
}

class EventStubForTestingPublicHandlingMethod implements Event
{
}
class EventStubForTestingMismatching implements Event
{
}
class EventStubForTestingTwoOrMoreHandlingMethodsPresent implements Event
{
}
class EventStubForTestingMissingHandlingMethod implements Event
{
}
class EventStubForTestingTwoOrMoreParametersPresentOnHandlingMethod implements Event
{
}
class EventStubForTestingOptionalParameterOnHandlingMethod implements Event
{
}
class EventStubForTestingNonPublicHandlingMethod implements Event
{
}
class Event7 implements Event
{
}
class EventWhichIsSubclassOfEvent7 extends Event7
{
}
class AnotherEventWhichIsSubclassOfEvent7 extends Event7
{
}
class Event8 implements Event
{
}
class EventWhichIsSubclassOfEvent8 extends Event8
{
}
class AnotherEventWhichIsSubclassOfEvent8 extends Event8
{
}
class EventStubForTestingApplyingViaCommand implements Event
{
}
class EventStubForTestingApplyingViaCommandResultingInException implements Event
{
}

class EventSourcedNonConsumer
{
    use Event\Sourcing;

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
