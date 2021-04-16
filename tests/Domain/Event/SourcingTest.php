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
    private ?AggregateRoot\Id $id1 = null;

    private ?AggregateRoot\Id $id2 = null;

    private ?Event\Envelope $event1 = null;

    private ?Event\Envelope $event2 = null;

    protected function setUp(): void
    {
        $this->id1 = new class('f5e65690-e50d-4312-a175-b004ec1bd42a') extends Domain\Id\UUID implements AggregateRoot\Id {
        };
        $this->id2 = new class('f84d8230-90a8-416f-af09-5ba315214888') extends Domain\Id\UUID implements AggregateRoot\Id {
        };
        $this->event1 = Event\Envelope::new($this->getMockBuilder(Event::class)->setMockClassName('event1')->getMockForAbstractClass(), $this->id1, 1);
        $this->event2 = Event\Envelope::new($this->getMockBuilder(Event::class)->setMockClassName('event2')->getMockForAbstractClass(), $this->id2, 1);
    }

    public function testSuccessfullyApplyingEventWithPublicHandlingMethod(): void
    {
        $event = new SourcingTest\EventStubForTestingPublicHandlingMethod();
        $event = Event\Envelope::new($event, $this->id1, 1);

        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id1);

        self::assertFalse($sourcing->isEventStubForTestingPublicHandlingMethodApplied());
        self::assertNull($sourcing->lastReplayed());
        self::assertNull($sourcing->lastEvent());
        self::assertEquals(0, $sourcing->version());
        self::assertEmpty($sourcing->events());

        $sourcing->replay(new InMemoryStream($event));

        self::assertTrue($sourcing->isEventStubForTestingPublicHandlingMethodApplied());
        self::assertSame($event, $sourcing->lastReplayed());
        self::assertEquals($event, $sourcing->lastEvent());
        self::assertEquals(1, $sourcing->version());
        self::assertEmpty($sourcing->events());

        $sourcing->commit();

        self::assertTrue($sourcing->isEventStubForTestingPublicHandlingMethodApplied());
        self::assertSame($event, $sourcing->lastReplayed());
        self::assertEquals($event, $sourcing->lastEvent());
        self::assertEquals(1, $sourcing->version());
        self::assertEmpty($sourcing->events());
    }

    public function testSuccessfullyApplyingEventWithNonPublicHandlingMethod(): void
    {
        $event = new SourcingTest\EventStubForTestingNonPublicHandlingMethod();
        $event = Event\Envelope::new($event, $this->id1, 1);
        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id1);

        self::assertFalse($sourcing->isEventStubForTestingNonPublicHandlingMethodApplied());

        $sourcing->replay(new InMemoryStream($event));

        self::assertTrue($sourcing->isEventStubForTestingNonPublicHandlingMethodApplied());
        self::assertSame($event, $sourcing->lastReplayed());
        self::assertEquals($event, $sourcing->lastEvent());
        self::assertEquals(1, $sourcing->version());
        self::assertEmpty($sourcing->events());
    }

    public function testAggregateWithMissingHandlingMethodForGivenEvent(): void
    {
        $event = new SourcingTest\EventStubForTestingMissingHandlingMethod();
        $event = Event\Envelope::new($event, $this->id1, 1);
        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id1);

        $exception = new Domain\Event\Exception\NoEventApplyingMethodFound($sourcing, $event);
        $this->expectExceptionObject($exception);

        $sourcing->replay(new InMemoryStream($event));
    }

    public function testAggregateWithTwoOrMoreHandlingMethodsPresentForGivenEvent(): void
    {
        $event = new SourcingTest\EventStubForTestingTwoOrMoreHandlingMethodsPresent();
        $event = Event\Envelope::new($event, $this->id1, 1);
        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id1);

        $exception = new Domain\Event\Exception\TooManyEventApplyingMethodsFound($sourcing, $event);
        $this->expectExceptionObject($exception);

        $sourcing->replay(new InMemoryStream($event));
    }

    public function testAggregateWithTwoOrMoreParametersPresentOnHandlingMethod(): void
    {
        $event = new SourcingTest\EventStubForTestingTwoOrMoreParametersPresentOnHandlingMethod();
        $event = Event\Envelope::new($event, $this->id1, 1);
        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id1);

        $exception = new Domain\Event\Exception\NoEventApplyingMethodFound($sourcing, $event);
        $this->expectExceptionObject($exception);

        $sourcing->replay(new InMemoryStream($event));
    }

    public function testAggregateWithOptionalParameterOnHandlingMethodForGivenEvent(): void
    {
        $event = new SourcingTest\EventStubForTestingOptionalParameterOnHandlingMethod();
        $event = Event\Envelope::new($event, $this->id1, 1);
        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id1);

        $exception = new Domain\Event\Exception\NoEventApplyingMethodFound($sourcing, $event);
        $this->expectExceptionObject($exception);

        $sourcing->replay(new InMemoryStream($event));
    }

    public function testApplyingMethodPresentForEventsParentClassOnly(): void
    {
        $event1 = new SourcingTest\EventWhichIsSubclassOfEvent7();
        $event1 = Event\Envelope::new($event1, $this->id1, 1);
        $event2 = new SourcingTest\AnotherEventWhichIsSubclassOfEvent7();
        $event2 = Event\Envelope::new($event2, $this->id1, 2);

        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id1);

        self::assertEquals(0, $sourcing->numberOfAppliesOfEvent7());

        $sourcing->replay(new InMemoryStream($event1, $event2));

        self::assertEquals(2, $sourcing->numberOfAppliesOfEvent7());
        self::assertSame($event2, $sourcing->lastReplayed());
        self::assertEquals($event2, $sourcing->lastEvent());
        self::assertEquals(2, $sourcing->version());
        self::assertEmpty($sourcing->events());
    }

//    public function testApplyingMethodPresentForEventsParentClassAndOnlyOneOfChildren()
//    {
//        $this->id
//            ->expects(self::exactly(2))
//            ->method('equals')
//            ->willReturn(true)
//        ;
//
//        $event1 = new SourcingTest\EventWhichIsSubclassOfEvent8($this->id);
//        $event2 = new SourcingTest\AnotherEventWhichIsSubclassOfEvent8($this->id);
//
//        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id);
//
//        self::assertFalse($sourcing->isEvent8Applied());
//        self::assertFalse($sourcing->isEvent8aApplied());
//
//        $sourcing->replay($event1, $event2);
//
//        self::assertTrue($sourcing->isEvent8Applied());
//        self::assertTrue($sourcing->isEvent8aApplied());
//        self::assertSame($event2, $sourcing->lastReplayed());
//        self::assertEmpty($sourcing->events());
//    }

    public function testApplyingEventViaCommand(): void
    {
        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id1);
        self::assertEmpty($sourcing->events());
        self::assertNull($sourcing->lastEvent());

        self::assertFalse($sourcing->isEvent9Applied());

        $sourcing->command1($this->id1);

        self::assertTrue($sourcing->isEvent9Applied());

        $event = new SourcingTest\EventStubForTestingApplyingViaCommand();

        self::assertNull($sourcing->lastReplayed());
        self::assertEquals($event, $sourcing->lastEvent()->message());
        self::assertEquals(0, $sourcing->version());
        self::assertEquals([$event], $sourcing->events());

        $sourcing->commit();

        self::assertNull($sourcing->lastReplayed());
        self::assertEquals($event, $sourcing->lastEvent()->message());
        self::assertEquals(1, $sourcing->version());
        self::assertEquals([], $sourcing->events());
    }

    public function testApplyingEventViaCommandResultingInAnException(): void
    {
        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id1);

        $exception = new \Exception('Command resulting in an exception');
        $this->expectExceptionObject($exception);

        try {
            self::assertEmpty($sourcing->events());
            self::assertNull($sourcing->lastEvent());
            self::assertNull($sourcing->lastReplayed());

            $sourcing->command2($this->id1);
        } catch (\Exception $thrown) {
            self::assertEmpty($sourcing->events());
            self::assertNull($sourcing->lastEvent());
            self::assertNull($sourcing->lastReplayed());
        } finally {
            self::assertTrue(isset($thrown));

            throw $thrown;
        }
    }

    public function testEventSourcingNonConsumer(): void
    {
        $sourcing = new Domain\Event\SourcingTest\EventSourcedNonConsumer($this->id1);

        $exception = new Domain\Event\Exception\SourcingObjectWithEventFailed($sourcing, $this->event1);
        $this->expectExceptionObject($exception);

        $sourcing->replay(new InMemoryStream($this->event1));
    }

    public function testEventAndConsumerMismatch(): void
    {
        $sourcing = new SourcingTest\EventSourcedAggregateRootStub($this->id1);

        $exception = new Domain\Exception\EventAndConsumerMismatch($sourcing, $this->event2);
        $this->expectExceptionObject($exception);

        $sourcing->replay(new InMemoryStream($this->event2));
    }
}

namespace Streak\Domain\Event\SourcingTest;

use Streak\Domain;
use Streak\Domain\AggregateRoot;
use Streak\Domain\Event;

class EventSourcedAggregateRootStub implements Event\Consumer
{
    use Event\Sourcing;

    private \Streak\Domain\Id $id;

    private bool $eventStubForTestingPublicHandlingMethodApplied = false;
    private bool $eventStubForTestingNonPublicHandlingMethodApplied = false;
    private int $numberOfAppliesOfEvent7 = 0;
    private bool $event8Applied = false;
    private bool $event8aApplied = false;
    private bool $event9Applied = false;

    public function __construct(AggregateRoot\Id $id)
    {
        $this->id = $id;
    }

    public function producerId(): Domain\Id
    {
        return $this->id;
    }

    public function applyEventStubForTestingPublicHandlingMethodApplied(EventStubForTestingPublicHandlingMethod $event): void
    {
        $this->eventStubForTestingPublicHandlingMethodApplied = true;
    }

    public function applyEvent2(EventStubForTestingTwoOrMoreHandlingMethodsPresent $event2): void
    {
    }

    public function applyEvent2Deux(EventStubForTestingTwoOrMoreHandlingMethodsPresent $event2): void
    {
    }

    public function applyEvent4(EventStubForTestingTwoOrMoreParametersPresentOnHandlingMethod $event, mixed $thisParameterUnneeded): void
    {
    }

    public function applyEvent5(EventStubForTestingOptionalParameterOnHandlingMethod $optionalEventIsInvalid = null): void
    {
    }

    public function command1(AggregateRoot\Id $id): void
    {
        $this->apply(new EventStubForTestingApplyingViaCommand());
    }

    public function command2(AggregateRoot\Id $id): void
    {
        $this->apply(new EventStubForTestingApplyingViaCommandResultingInException());
    }

    public function isEventStubForTestingPublicHandlingMethodApplied(): bool
    {
        return $this->eventStubForTestingPublicHandlingMethodApplied;
    }

    public function isEventStubForTestingNonPublicHandlingMethodApplied(): bool
    {
        return $this->eventStubForTestingNonPublicHandlingMethodApplied;
    }

    public function isEvent8Applied(): bool
    {
        return $this->event8Applied;
    }

    public function isEvent8aApplied(): bool
    {
        return $this->event8aApplied;
    }

    public function isEvent9Applied(): bool
    {
        return $this->event9Applied;
    }

    public function numberOfAppliesOfEvent7(): int
    {
        return $this->numberOfAppliesOfEvent7;
    }

    public function id(): Domain\Id
    {
        return $this->id;
    }

    private function applyEventStubForTestingNonPublicHandlingMethodApplied(EventStubForTestingNonPublicHandlingMethod $event): void
    {
        $this->eventStubForTestingNonPublicHandlingMethodApplied = true;
    }

    private function applyEvent7(Event7 $event): void
    {
        ++$this->numberOfAppliesOfEvent7;
    }

    private function applyEvent8(Event8 $event): void
    {
        $this->event8Applied = true;
    }

    private function applyEvent8a(EventWhichIsSubclassOfEvent8 $event): void
    {
        $this->event8aApplied = true;
    }

    private function applyEvent9(EventStubForTestingApplyingViaCommand $event): void
    {
        $this->event9Applied = true;
    }

    private function applyEvent10(EventStubForTestingApplyingViaCommandResultingInException $event): void
    {
        throw new \Exception('Command resulting in an exception');
    }

    private function applyNonEvent(\stdClass $parameter): void
    {
    }

    private function applySomethingElse($parameters): void
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

    private \Streak\Domain\Id $id;

    public function __construct(AggregateRoot\Id $id)
    {
        $this->id = $id;
    }

    public function producerId(): Domain\Id
    {
        return $this->id;
    }
}
