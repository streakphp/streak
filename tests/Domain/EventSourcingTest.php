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

namespace Streak\Domain;

use PHPUnit\Framework\TestCase;
use Streak\Domain\EventSourcingTest\EventSourcedAggregateRootStub;
use Streak\Domain\EventSourcingTest\EventSourcedAggregateRootStubId;
use Streak\Domain\EventSourcingTest\EventSourcedAggregateStub;
use Streak\Domain\EventSourcingTest\EventSourcedAggregateStubId;
use Streak\Domain\EventSourcingTest\EventSourcedEntityStub;
use Streak\Domain\EventSourcingTest\EventSourcedEntityStubId;
use Streak\Domain\Exception\EventMismatched;
use Streak\Infrastructure\Domain\Event\InMemoryStream;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\AggregateRoot\EventSourcing
 * @covers \Streak\Domain\Aggregate\EventSourcing
 * @covers \Streak\Domain\Entity\EventSourcing
 */
class EventSourcingTest extends TestCase
{
    public function testSuccessfullyApplyingEventOnAggregateRoot(): void
    {
        $aggregateRootId1 = new EventSourcedAggregateRootStubId('f5e65690-e50d-4312-a175-b004ec1bd42a');
        $aggregateRoot1 = new EventSourcedAggregateRootStub($aggregateRootId1);

        $event = new EventSourcingTest\Event1();

        self::assertNull($aggregateRoot1->lastEvent());
        self::assertSame(0, $aggregateRoot1->version());
        self::assertEmpty($aggregateRoot1->events());
        self::assertEmpty($aggregateRoot1->appliedEvents());

        $aggregateRoot1->command($event);

        self::assertEquals($event, $aggregateRoot1->lastEvent());
        self::assertSame(0, $aggregateRoot1->version());
        self::assertEquals([$event], $aggregateRoot1->events());
        self::assertEquals([$event], $aggregateRoot1->appliedEvents());

        $stream = $aggregateRoot1->events();

        $aggregateRoot1->commit();

        self::assertEquals($event, $aggregateRoot1->lastEvent());
        self::assertSame(1, $aggregateRoot1->version());
        self::assertEmpty($aggregateRoot1->events());
        self::assertEquals([$event], $aggregateRoot1->appliedEvents());

        $freshAggregateRoot1 = new EventSourcedAggregateRootStub($aggregateRootId1);
        $freshAggregateRoot1->replay(new InMemoryStream(...$stream));

        self::assertNotSame($aggregateRoot1, $freshAggregateRoot1);
        self::assertEquals($aggregateRoot1, $freshAggregateRoot1);
    }

    public function testSuccessfullyApplyingEventOnAggregateRootButNotAggregate(): void
    {
        $aggregate1 = new EventSourcedAggregateStub(new EventSourcedAggregateStubId('51b37ad0-9e18-47fb-89ba-8d860472c852'));
        $aggregateRoot1 = new EventSourcedAggregateRootStub(new EventSourcedAggregateRootStubId('f5e65690-e50d-4312-a175-b004ec1bd42a'), $aggregate1);
        $aggregate1->registerAggregateRoot($aggregateRoot1);

        $event = new EventSourcingTest\Event1();

        self::assertNull($aggregateRoot1->lastEvent());
        self::assertSame(0, $aggregateRoot1->version());
        self::assertEmpty($aggregateRoot1->events());
        self::assertEmpty($aggregateRoot1->appliedEvents());
        self::assertEmpty($aggregate1->appliedEvents());

        $aggregateRoot1->command($event);

        self::assertEquals($event, $aggregateRoot1->lastEvent());
        self::assertSame(0, $aggregateRoot1->version());
        self::assertEquals([$event], $aggregateRoot1->events());
        self::assertEquals([$event], $aggregateRoot1->appliedEvents());
        self::assertEmpty($aggregate1->appliedEvents());

        $stream = $aggregateRoot1->events();

        $aggregateRoot1->commit();

        self::assertEquals($event, $aggregateRoot1->lastEvent());
        self::assertSame(1, $aggregateRoot1->version());
        self::assertEmpty($aggregateRoot1->events());
        self::assertEquals([$event], $aggregateRoot1->appliedEvents());
        self::assertEmpty($aggregate1->appliedEvents());

        $freshAggregate1 = new EventSourcedAggregateStub(new EventSourcedAggregateStubId('51b37ad0-9e18-47fb-89ba-8d860472c852'));
        $freshAggregateRoot1 = new EventSourcedAggregateRootStub(new EventSourcedAggregateRootStubId('f5e65690-e50d-4312-a175-b004ec1bd42a'), $freshAggregate1);
        $freshAggregate1->registerAggregateRoot($freshAggregateRoot1);

        $freshAggregateRoot1->replay(new InMemoryStream(...$stream));

        self::assertNotSame($aggregateRoot1, $freshAggregateRoot1);
        self::assertEquals($aggregateRoot1, $freshAggregateRoot1);
    }

    public function testSuccessfullyApplyingEventOnAggregateRootButNotAggregateAndEntity(): void
    {
        $entity1 = new EventSourcedEntityStub(new EventSourcedEntityStubId('bafbcfd1-0355-42b4-bd3f-0a5379570574'));
        $aggregate1 = new EventSourcedAggregateStub(new EventSourcedAggregateStubId('51b37ad0-9e18-47fb-89ba-8d860472c852'), $entity1);
        $aggregateRoot1 = new EventSourcedAggregateRootStub(new EventSourcedAggregateRootStubId('f5e65690-e50d-4312-a175-b004ec1bd42a'), $aggregate1);
        $aggregate1->registerAggregateRoot($aggregateRoot1);
        $entity1->registerAggregate($aggregate1);

        $event = new EventSourcingTest\Event1();

        self::assertNull($aggregateRoot1->lastEvent());
        self::assertSame(0, $aggregateRoot1->version());
        self::assertEmpty($aggregateRoot1->events());
        self::assertEmpty($aggregateRoot1->appliedEvents());
        self::assertEmpty($aggregate1->appliedEvents());
        self::assertEmpty($entity1->appliedEvents());

        $aggregateRoot1->command($event);

        self::assertEquals($event, $aggregateRoot1->lastEvent());
        self::assertSame(0, $aggregateRoot1->version());
        self::assertEquals([$event], $aggregateRoot1->events());
        self::assertEquals([$event], $aggregateRoot1->appliedEvents());
        self::assertEmpty($aggregate1->appliedEvents());
        self::assertEmpty($entity1->appliedEvents());

        $stream = $aggregateRoot1->events();

        $aggregateRoot1->commit();

        self::assertEquals($event, $aggregateRoot1->lastEvent());
        self::assertSame(1, $aggregateRoot1->version());
        self::assertEmpty($aggregateRoot1->events());
        self::assertEquals([$event], $aggregateRoot1->appliedEvents());
        self::assertEmpty($aggregate1->appliedEvents());
        self::assertEmpty($entity1->appliedEvents());

        $freshEntity1 = new EventSourcedEntityStub(new EventSourcedEntityStubId('bafbcfd1-0355-42b4-bd3f-0a5379570574'));
        $freshAggregate1 = new EventSourcedAggregateStub(new EventSourcedAggregateStubId('51b37ad0-9e18-47fb-89ba-8d860472c852'), $freshEntity1);
        $freshAggregateRoot1 = new EventSourcedAggregateRootStub(new EventSourcedAggregateRootStubId('f5e65690-e50d-4312-a175-b004ec1bd42a'), $freshAggregate1);
        $freshAggregate1->registerAggregateRoot($freshAggregateRoot1);
        $freshEntity1->registerAggregate($freshAggregate1);

        $freshAggregateRoot1->replay(new InMemoryStream(...$stream));

        self::assertNotSame($aggregateRoot1, $freshAggregateRoot1);
        self::assertEquals($aggregateRoot1, $freshAggregateRoot1);
    }

    public function testSuccessfullyApplyingEventOnAggregateAndAggregateRoot(): void
    {
        $aggregate1 = new EventSourcedAggregateStub(new EventSourcedAggregateStubId('51b37ad0-9e18-47fb-89ba-8d860472c852'));
        $aggregateRoot1 = new EventSourcedAggregateRootStub(new EventSourcedAggregateRootStubId('f5e65690-e50d-4312-a175-b004ec1bd42a'), $aggregate1);
        $aggregate1->registerAggregateRoot($aggregateRoot1);

        $event = new EventSourcingTest\Event1();

        self::assertNull($aggregateRoot1->lastEvent());
        self::assertSame(0, $aggregateRoot1->version());
        self::assertEmpty($aggregateRoot1->events());
        self::assertEmpty($aggregateRoot1->appliedEvents());
        self::assertEmpty($aggregate1->appliedEvents());

        $aggregateRoot1->commandOnAggregate1($event);

        self::assertEquals($event, $aggregateRoot1->lastEvent());
        self::assertSame(0, $aggregateRoot1->version());
        self::assertEquals([$event], $aggregateRoot1->events());
        self::assertEquals([$event], $aggregateRoot1->appliedEvents());
        self::assertEquals([$event], $aggregate1->appliedEvents());

        $stream = $aggregateRoot1->events();

        $aggregateRoot1->commit();

        self::assertEquals($event, $aggregateRoot1->lastEvent());
        self::assertSame(1, $aggregateRoot1->version());
        self::assertEmpty($aggregateRoot1->events());
        self::assertEquals([$event], $aggregateRoot1->appliedEvents());
        self::assertEquals([$event], $aggregate1->appliedEvents());

        $freshAggregate1 = new EventSourcedAggregateStub(new EventSourcedAggregateStubId('51b37ad0-9e18-47fb-89ba-8d860472c852'));
        $freshAggregateRoot1 = new EventSourcedAggregateRootStub(new EventSourcedAggregateRootStubId('f5e65690-e50d-4312-a175-b004ec1bd42a'), $freshAggregate1);
        $freshAggregate1->registerAggregateRoot($freshAggregateRoot1);

        $freshAggregateRoot1->replay(new InMemoryStream(...$stream));

        self::assertNotSame($aggregateRoot1, $freshAggregateRoot1);
        self::assertEquals($aggregateRoot1, $freshAggregateRoot1);
    }

    public function testSuccessfullyApplyingEventOnAggregateAndAggregateRootButNotEntity(): void
    {
        $entity1 = new EventSourcedEntityStub(new EventSourcedEntityStubId('bafbcfd1-0355-42b4-bd3f-0a5379570574'));
        $aggregate1 = new EventSourcedAggregateStub(new EventSourcedAggregateStubId('51b37ad0-9e18-47fb-89ba-8d860472c852'), $entity1);
        $aggregateRoot1 = new EventSourcedAggregateRootStub(new EventSourcedAggregateRootStubId('f5e65690-e50d-4312-a175-b004ec1bd42a'), $aggregate1);
        $aggregate1->registerAggregateRoot($aggregateRoot1);
        $entity1->registerAggregate($aggregate1);

        $event = new EventSourcingTest\Event1();

        self::assertNull($aggregateRoot1->lastEvent());
        self::assertSame(0, $aggregateRoot1->version());
        self::assertEmpty($aggregateRoot1->events());
        self::assertEmpty($aggregateRoot1->appliedEvents());
        self::assertEmpty($aggregate1->appliedEvents());
        self::assertEmpty($entity1->appliedEvents());

        $aggregateRoot1->commandOnAggregate1($event);

        self::assertEquals($event, $aggregateRoot1->lastEvent());
        self::assertSame(0, $aggregateRoot1->version());
        self::assertEquals([$event], $aggregateRoot1->events());
        self::assertEquals([$event], $aggregateRoot1->appliedEvents());
        self::assertEquals([$event], $aggregate1->appliedEvents());
        self::assertEmpty($entity1->appliedEvents());

        $stream = $aggregateRoot1->events();

        $aggregateRoot1->commit();

        self::assertEquals($event, $aggregateRoot1->lastEvent());
        self::assertSame(1, $aggregateRoot1->version());
        self::assertEmpty($aggregateRoot1->events());
        self::assertEquals([$event], $aggregateRoot1->appliedEvents());
        self::assertEquals([$event], $aggregate1->appliedEvents());
        self::assertEmpty($entity1->appliedEvents());

        $freshEntity1 = new EventSourcedEntityStub(new EventSourcedEntityStubId('bafbcfd1-0355-42b4-bd3f-0a5379570574'));
        $freshAggregate1 = new EventSourcedAggregateStub(new EventSourcedAggregateStubId('51b37ad0-9e18-47fb-89ba-8d860472c852'), $freshEntity1);
        $freshAggregateRoot1 = new EventSourcedAggregateRootStub(new EventSourcedAggregateRootStubId('f5e65690-e50d-4312-a175-b004ec1bd42a'), $freshAggregate1);
        $freshAggregate1->registerAggregateRoot($freshAggregateRoot1);
        $freshEntity1->registerAggregate($freshAggregate1);

        $freshAggregateRoot1->replay(new InMemoryStream(...$stream));

        self::assertNotSame($aggregateRoot1, $freshAggregateRoot1);
        self::assertEquals($aggregateRoot1, $freshAggregateRoot1);
    }

    public function testSuccessfullyApplyingEventOnAggregateButNotAggregateRoot(): void
    {
        $aggregate1 = new EventSourcedAggregateStub(new EventSourcedAggregateStubId('51b37ad0-9e18-47fb-89ba-8d860472c852'));
        $aggregateRoot1 = new EventSourcedAggregateRootStub(new EventSourcedAggregateRootStubId('f5e65690-e50d-4312-a175-b004ec1bd42a'), $aggregate1);
        $aggregate1->registerAggregateRoot($aggregateRoot1);

        $event = new EventSourcingTest\Event2();

        self::assertNull($aggregateRoot1->lastEvent());
        self::assertSame(0, $aggregateRoot1->version());
        self::assertEmpty($aggregateRoot1->events());
        self::assertEmpty($aggregateRoot1->appliedEvents());
        self::assertEmpty($aggregate1->appliedEvents());

        $aggregateRoot1->commandOnAggregate1($event);

        self::assertEquals($event, $aggregateRoot1->lastEvent());
        self::assertSame(0, $aggregateRoot1->version());
        self::assertEquals([$event], $aggregateRoot1->events());
        self::assertEmpty($aggregateRoot1->appliedEvents());
        self::assertEquals([$event], $aggregate1->appliedEvents());

        $stream = $aggregateRoot1->events();

        $aggregateRoot1->commit();

        self::assertEquals($event, $aggregateRoot1->lastEvent());
        self::assertSame(1, $aggregateRoot1->version());
        self::assertEmpty($aggregateRoot1->events());
        self::assertEmpty($aggregateRoot1->appliedEvents());
        self::assertEquals([$event], $aggregate1->appliedEvents());

        $freshAggregate1 = new EventSourcedAggregateStub(new EventSourcedAggregateStubId('51b37ad0-9e18-47fb-89ba-8d860472c852'));
        $freshAggregateRoot1 = new EventSourcedAggregateRootStub(new EventSourcedAggregateRootStubId('f5e65690-e50d-4312-a175-b004ec1bd42a'), $freshAggregate1);
        $freshAggregate1->registerAggregateRoot($freshAggregateRoot1);

        $freshAggregateRoot1->replay(new InMemoryStream(...$stream));

        self::assertNotSame($aggregateRoot1, $freshAggregateRoot1);
        self::assertEquals($aggregateRoot1, $freshAggregateRoot1);
    }

    public function testSuccessfullyApplyingEventOnEntityAndAggregateAndAggregateRoot(): void
    {
        $entity1 = new EventSourcedEntityStub(new EventSourcedEntityStubId('bafbcfd1-0355-42b4-bd3f-0a5379570574'));
        $aggregate1 = new EventSourcedAggregateStub(new EventSourcedAggregateStubId('51b37ad0-9e18-47fb-89ba-8d860472c852'), $entity1);
        $aggregateRoot1 = new EventSourcedAggregateRootStub(new EventSourcedAggregateRootStubId('f5e65690-e50d-4312-a175-b004ec1bd42a'), $aggregate1);
        $aggregate1->registerAggregateRoot($aggregateRoot1);
        $entity1->registerAggregate($aggregate1);

        $event = new EventSourcingTest\Event1();

        self::assertNull($aggregateRoot1->lastEvent());
        self::assertSame(0, $aggregateRoot1->version());
        self::assertEmpty($aggregateRoot1->events());
        self::assertEmpty($aggregateRoot1->appliedEvents());
        self::assertEmpty($aggregate1->appliedEvents());
        self::assertEmpty($entity1->appliedEvents());

        $aggregateRoot1->commandOnEntity1OfAggregate1($event);

        self::assertEquals($event, $aggregateRoot1->lastEvent());
        self::assertSame(0, $aggregateRoot1->version());
        self::assertEquals([$event], $aggregateRoot1->events());
        self::assertEquals([$event], $aggregateRoot1->appliedEvents());
        self::assertEquals([$event], $aggregate1->appliedEvents());
        self::assertEquals([$event], $entity1->appliedEvents());

        $stream = $aggregateRoot1->events();

        $aggregateRoot1->commit();

        self::assertEquals($event, $aggregateRoot1->lastEvent());
        self::assertSame(1, $aggregateRoot1->version());
        self::assertEmpty($aggregateRoot1->events());
        self::assertEquals([$event], $aggregateRoot1->appliedEvents());
        self::assertEquals([$event], $aggregate1->appliedEvents());
        self::assertEquals([$event], $entity1->appliedEvents());

        $freshEntity1 = new EventSourcedEntityStub(new EventSourcedEntityStubId('bafbcfd1-0355-42b4-bd3f-0a5379570574'));
        $freshAggregate1 = new EventSourcedAggregateStub(new EventSourcedAggregateStubId('51b37ad0-9e18-47fb-89ba-8d860472c852'), $freshEntity1);
        $freshAggregateRoot1 = new EventSourcedAggregateRootStub(new EventSourcedAggregateRootStubId('f5e65690-e50d-4312-a175-b004ec1bd42a'), $freshAggregate1);
        $freshAggregate1->registerAggregateRoot($freshAggregateRoot1);
        $freshEntity1->registerAggregate($freshAggregate1);

        $freshAggregateRoot1->replay(new InMemoryStream(...$stream));

        self::assertNotSame($aggregateRoot1, $freshAggregateRoot1);
        self::assertEquals($aggregateRoot1, $freshAggregateRoot1);
    }

    public function testSuccessfullyApplyingEventOnEntityAndAggregateButNotAggregateRoot(): void
    {
        $entity1 = new EventSourcedEntityStub(new EventSourcedEntityStubId('bafbcfd1-0355-42b4-bd3f-0a5379570574'));
        $aggregate1 = new EventSourcedAggregateStub(new EventSourcedAggregateStubId('51b37ad0-9e18-47fb-89ba-8d860472c852'), $entity1);
        $aggregateRoot1 = new EventSourcedAggregateRootStub(new EventSourcedAggregateRootStubId('f5e65690-e50d-4312-a175-b004ec1bd42a'), $aggregate1);
        $aggregate1->registerAggregateRoot($aggregateRoot1);
        $entity1->registerAggregate($aggregate1);

        $event = new EventSourcingTest\Event2();

        self::assertNull($aggregateRoot1->lastEvent());
        self::assertSame(0, $aggregateRoot1->version());
        self::assertEmpty($aggregateRoot1->events());
        self::assertEmpty($aggregateRoot1->appliedEvents());
        self::assertEmpty($aggregate1->appliedEvents());
        self::assertEmpty($entity1->appliedEvents());

        $aggregateRoot1->commandOnEntity1OfAggregate1($event);

        self::assertEquals($event, $aggregateRoot1->lastEvent());
        self::assertSame(0, $aggregateRoot1->version());
        self::assertEquals([$event], $aggregateRoot1->events());
        self::assertEmpty($aggregateRoot1->appliedEvents());
        self::assertEquals([$event], $aggregate1->appliedEvents());
        self::assertEquals([$event], $entity1->appliedEvents());

        $stream = $aggregateRoot1->events();

        $aggregateRoot1->commit();

        self::assertEquals($event, $aggregateRoot1->lastEvent());
        self::assertSame(1, $aggregateRoot1->version());
        self::assertEmpty($aggregateRoot1->events());
        self::assertEmpty($aggregateRoot1->appliedEvents());
        self::assertEquals([$event], $aggregate1->appliedEvents());
        self::assertEquals([$event], $entity1->appliedEvents());

        $freshEntity1 = new EventSourcedEntityStub(new EventSourcedEntityStubId('bafbcfd1-0355-42b4-bd3f-0a5379570574'));
        $freshAggregate1 = new EventSourcedAggregateStub(new EventSourcedAggregateStubId('51b37ad0-9e18-47fb-89ba-8d860472c852'), $freshEntity1);
        $freshAggregateRoot1 = new EventSourcedAggregateRootStub(new EventSourcedAggregateRootStubId('f5e65690-e50d-4312-a175-b004ec1bd42a'), $freshAggregate1);
        $freshAggregate1->registerAggregateRoot($freshAggregateRoot1);
        $freshEntity1->registerAggregate($freshAggregate1);

        $freshAggregateRoot1->replay(new InMemoryStream(...$stream));

        self::assertNotSame($aggregateRoot1, $freshAggregateRoot1);
        self::assertEquals($aggregateRoot1, $freshAggregateRoot1);
    }

    public function testSuccessfullyApplyingEventOnEntityAndAggregateRootButNotAggregate(): void
    {
        $entity1 = new EventSourcedEntityStub(new EventSourcedEntityStubId('bafbcfd1-0355-42b4-bd3f-0a5379570574'));
        $aggregate1 = new EventSourcedAggregateStub(new EventSourcedAggregateStubId('51b37ad0-9e18-47fb-89ba-8d860472c852'), $entity1);
        $aggregateRoot1 = new EventSourcedAggregateRootStub(new EventSourcedAggregateRootStubId('f5e65690-e50d-4312-a175-b004ec1bd42a'), $aggregate1);
        $aggregate1->registerAggregateRoot($aggregateRoot1);
        $entity1->registerAggregate($aggregate1);

        $event = new EventSourcingTest\Event4();

        self::assertNull($aggregateRoot1->lastEvent());
        self::assertSame(0, $aggregateRoot1->version());
        self::assertEmpty($aggregateRoot1->events());
        self::assertEmpty($aggregateRoot1->appliedEvents());
        self::assertEmpty($aggregate1->appliedEvents());
        self::assertEmpty($entity1->appliedEvents());

        $aggregateRoot1->commandOnEntity1OfAggregate1($event);

        self::assertEquals($event, $aggregateRoot1->lastEvent());
        self::assertSame(0, $aggregateRoot1->version());
        self::assertEquals([$event], $aggregateRoot1->events());
        self::assertEquals([$event], $aggregateRoot1->appliedEvents());
        self::assertEmpty($aggregate1->appliedEvents());
        self::assertEquals([$event], $entity1->appliedEvents());

        $stream = $aggregateRoot1->events();

        $aggregateRoot1->commit();

        self::assertEquals($event, $aggregateRoot1->lastEvent());
        self::assertSame(1, $aggregateRoot1->version());
        self::assertEmpty($aggregateRoot1->events());
        self::assertEquals([$event], $aggregateRoot1->appliedEvents());
        self::assertEmpty($aggregate1->appliedEvents());
        self::assertEquals([$event], $entity1->appliedEvents());

        $freshEntity1 = new EventSourcedEntityStub(new EventSourcedEntityStubId('bafbcfd1-0355-42b4-bd3f-0a5379570574'));
        $freshAggregate1 = new EventSourcedAggregateStub(new EventSourcedAggregateStubId('51b37ad0-9e18-47fb-89ba-8d860472c852'), $freshEntity1);
        $freshAggregateRoot1 = new EventSourcedAggregateRootStub(new EventSourcedAggregateRootStubId('f5e65690-e50d-4312-a175-b004ec1bd42a'), $freshAggregate1);
        $freshAggregate1->registerAggregateRoot($freshAggregateRoot1);
        $freshEntity1->registerAggregate($freshAggregate1);

        $freshAggregateRoot1->replay(new InMemoryStream(...$stream));

        self::assertNotSame($aggregateRoot1, $freshAggregateRoot1);
        self::assertEquals($aggregateRoot1, $freshAggregateRoot1);
    }

    public function testMismatchedEventOnAggregateRoot(): void
    {
        $entity1 = new EventSourcedEntityStub(new EventSourcedEntityStubId('bafbcfd1-0355-42b4-bd3f-0a5379570574'));
        $aggregate1 = new EventSourcedAggregateStub(new EventSourcedAggregateStubId('51b37ad0-9e18-47fb-89ba-8d860472c852'), $entity1);
        $aggregateRoot1 = new EventSourcedAggregateRootStub(new EventSourcedAggregateRootStubId('f5e65690-e50d-4312-a175-b004ec1bd42a'), $aggregate1);
        $aggregate1->registerAggregateRoot($aggregateRoot1);
        $entity1->registerAggregate($aggregate1);

        $event = new EventSourcingTest\Event4();
        $event = Event\Envelope::new($event, new EventSourcedAggregateStubId('61888494-fd58-412c-86c3-03cf81aca443'), 1);

        try {
            $aggregateRoot1->applyEvent($event);
            self::fail();
        } catch (EventMismatched $exception) {
            self::assertSame($aggregateRoot1, $exception->object());
            self::assertSame($event, $exception->event());
        }
    }

    public function testMismatchedEventOnAggregate(): void
    {
        $entity1 = new EventSourcedEntityStub(new EventSourcedEntityStubId('bafbcfd1-0355-42b4-bd3f-0a5379570574'));
        $aggregate1 = new EventSourcedAggregateStub(new EventSourcedAggregateStubId('51b37ad0-9e18-47fb-89ba-8d860472c852'), $entity1);
        $aggregateRoot1 = new EventSourcedAggregateRootStub(new EventSourcedAggregateRootStubId('f5e65690-e50d-4312-a175-b004ec1bd42a'), $aggregate1);
        $aggregate1->registerAggregateRoot($aggregateRoot1);
        $entity1->registerAggregate($aggregate1);

        $event = new EventSourcingTest\Event2();
        $event = Event\Envelope::new($event, new EventSourcedAggregateStubId('61888494-fd58-412c-86c3-03cf81aca443'), 1);

        try {
            $aggregate1->applyEvent($event);
            self::fail();
        } catch (EventMismatched $exception) {
            self::assertSame($aggregate1, $exception->object());
            self::assertSame($event, $exception->event());
        }
    }

    public function testMismatchedEventOnEntity(): void
    {
        $entity1 = new EventSourcedEntityStub(new EventSourcedEntityStubId('bafbcfd1-0355-42b4-bd3f-0a5379570574'));
        $aggregate1 = new EventSourcedAggregateStub(new EventSourcedAggregateStubId('51b37ad0-9e18-47fb-89ba-8d860472c852'), $entity1);
        $aggregateRoot1 = new EventSourcedAggregateRootStub(new EventSourcedAggregateRootStubId('f5e65690-e50d-4312-a175-b004ec1bd42a'), $aggregate1);
        $aggregate1->registerAggregateRoot($aggregateRoot1);
        $entity1->registerAggregate($aggregate1);

        $event = new EventSourcingTest\Event2();
        $event = Event\Envelope::new($event, new EventSourcedAggregateStubId('61888494-fd58-412c-86c3-03cf81aca443'), 1);

        try {
            $entity1->applyEvent($event);
            self::fail();
        } catch (EventMismatched $exception) {
            self::assertSame($entity1, $exception->object());
            self::assertSame($event, $exception->event());
        }
    }

    public function testRegisteringAggregateOnItself(): void
    {
        $this->expectExceptionObject(new \BadMethodCallException('You can\'t register aggregate on itself.'));

        $aggregate1 = new EventSourcedAggregateStub(new EventSourcedAggregateStubId('51b37ad0-9e18-47fb-89ba-8d860472c852'));
        $aggregate1->registerAggregate($aggregate1);
    }

    public function testObtainingAggregateRootOnAggregateWhenItsNotRegistered(): void
    {
        $this->expectExceptionObject(new \BadMethodCallException('Aggregate root no registered. Did you forget to run Streak\Domain\EventSourcingTest\EventSourcedAggregateStub::registerAggregateRoot()?'));

        $aggregate1 = new EventSourcedAggregateStub(new EventSourcedAggregateStubId('51b37ad0-9e18-47fb-89ba-8d860472c852'));

        $aggregate1->aggregateRoot();
    }

    public function testObtainingAggregateRootOnEntityWhenItsNotRegistered(): void
    {
        $this->expectExceptionObject(new \BadMethodCallException('Aggregate root no registered. Did you forget to run Streak\Domain\EventSourcingTest\EventSourcedEntityStub::registerAggregateRoot()?'));

        $entity1 = new EventSourcedEntityStub(new EventSourcedEntityStubId('bafbcfd1-0355-42b4-bd3f-0a5379570574'));

        $entity1->aggregateRoot();
    }
}

namespace Streak\Domain\EventSourcingTest;

use Streak\Domain;
use Streak\Domain\Aggregate;
use Streak\Domain\AggregateRoot;
use Streak\Domain\Entity;
use Streak\Domain\Event;

class EventSourcedAggregateRootStubId extends Domain\Id\UUID implements AggregateRoot\Id
{
}

class EventSourcedAggregateRootStub implements Event\Sourced\AggregateRoot
{
    use AggregateRoot\Comparison;
    use AggregateRoot\EventSourcing;
    use AggregateRoot\Identification;

    private array $appliedEvents = [];

    public function __construct(
        EventSourcedAggregateRootStubId $id,
        private ?EventSourcedAggregateStub $aggregate1 = null,
        private ?EventSourcedAggregateStub $aggregate2 = null,
        private ?EventSourcedEntityStub $entity1 = null,
        private ?EventSourcedEntityStub $entity2 = null,
    ) {
        $this->identifyBy($id);
    }

    public function entity1(): ?Event\Sourced\Entity
    {
        return $this->entity1;
    }

    public function entity2(): ?Event\Sourced\Entity
    {
        return $this->entity2;
    }

    public function command(Event $event): void
    {
        $this->apply($event);
    }

    public function commandOnAggregate1(Event $event): void
    {
        $this->aggregate1->commandOnAggregate($event);
    }

    public function commandOnEntity1OfAggregate1(Event $event): void
    {
        $this->aggregate1->commandOnEntity1($event);
    }

    public function commandOnEntity2OfAggregate1(Event $event): void
    {
        $this->aggregate1->commandOnEntity2($event);
    }

    public function commandOnEntity1(Event $event): void
    {
        $this->entity1->command($event);
    }

    public function commandOnAggregate2(Event $event): void
    {
        $this->aggregate2->commandOnAggregate($event);
    }

    public function commandOnEntity1OfAggregate2(Event $event): void
    {
        $this->aggregate2->commandOnEntity1($event);
    }

    public function commandOnEntity2OfAggregate2(Event $event): void
    {
        $this->aggregate2->commandOnEntity2($event);
    }

    public function commandOnEntity2(Event $event): void
    {
        $this->entity1->command($event);
    }

    public function appliedEvents(): array
    {
        return $this->appliedEvents;
    }

    private function applyEvent1(Event1 $event): void
    {
        $this->appliedEvents = [$event];
    }

    private function applyEvent4(Event4 $event): void
    {
        $this->appliedEvents[] = $event;
    }
}

class EventSourcedAggregateStubId extends Domain\Id\UUID implements Aggregate\Id
{
}

class EventSourcedAggregateStub implements Event\Sourced\Aggregate
{
    use Aggregate\Comparison;
    use Aggregate\EventSourcing;
    use Aggregate\Identification;

    /**
     * @var Event\Envelope[]
     */
    private array $appliedEvents = [];

    public function __construct(
        EventSourcedAggregateStubId $id,
        private ?EventSourcedEntityStub $entity1 = null,
        private ?EventSourcedEntityStub $entity2 = null,
    ) {
        $this->identifyBy($id);
    }

    public function commandOnAggregate(Event $event): void
    {
        $this->apply($event);
    }

    public function commandOnEntity1(Event $event): void
    {
        $this->entity1->command($event);
    }

    public function commandOnEntity2(Event $event): void
    {
        $this->entity2->command($event);
    }

    public function appliedEvents(): array
    {
        return $this->appliedEvents;
    }

    private function applyEvent1(Event1 $event): void
    {
        $this->appliedEvents = [$event];
    }

    private function applyEvent2(Event2 $event): void
    {
        $this->appliedEvents = [$event];
    }
}

class EventSourcedEntityStubId extends Domain\Id\UUID implements Entity\Id
{
}

class EventSourcedEntityStub implements Event\Sourced\Entity
{
    use Entity\Comparison;
    use Entity\EventSourcing;
    use Entity\Identification;

    /**
     * @var Event\Envelope[]
     */
    private array $appliedEvents = [];

    public function __construct(EventSourcedEntityStubId $id)
    {
        $this->identifyBy($id);
    }

    public function command(Event $event): void
    {
        $this->apply($event);
    }

    public function appliedEvents(): array
    {
        return $this->appliedEvents;
    }

    private function applyEvent1(Event1 $event): void
    {
        $this->appliedEvents[] = $event;
    }

    private function applyEvent2(Event2 $event): void
    {
        $this->appliedEvents[] = $event;
    }

    private function applyEvent3(Event3 $event): void
    {
        $this->appliedEvents[] = $event;
    }

    private function applyEvent4(Event4 $event): void
    {
        $this->appliedEvents[] = $event;
    }
}

class Event1 implements Event
{
}

class Event2 implements Event
{
}

class Event3 implements Event
{
}

class Event4 implements Event
{
}
