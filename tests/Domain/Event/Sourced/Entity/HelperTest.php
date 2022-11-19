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

namespace Streak\Domain\Event\Sourced\Entity;

use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\Event\Sourced\Entity\HelperTest\EventSourcedEntityStub;
use Streak\Domain\Event\Sourced\Entity\HelperTest\EventSourcedEntityStubId;

/**
 * @covers \Streak\Domain\Event\Sourced\Entity\Helper
 */
class HelperTest extends TestCase
{
    public function testExtractingEventSourcedEntities(): void
    {
        $entity5 = new EventSourcedEntityStub(new EventSourcedEntityStubId('a1880189-03a6-43aa-963f-610002a77db5'));
        $entity4 = new EventSourcedEntityStub(new EventSourcedEntityStubId('a1880189-03a6-43aa-963f-610002a77db4'));
        $entity3 = new EventSourcedEntityStub(new EventSourcedEntityStubId('a1880189-03a6-43aa-963f-610002a77db3'));
        $entity2 = new EventSourcedEntityStub(new EventSourcedEntityStubId('a1880189-03a6-43aa-963f-610002a77db2'), $entity3, $entity5);
        $entity1 = new EventSourcedEntityStub(new EventSourcedEntityStubId('a1880189-03a6-43aa-963f-610002a77db1'), $entity2, $entity4);

        $helper = Helper::for($entity1);

        $actual = $helper->extractEventSourcedEntities();
        $actual = iterator_to_array($actual);

        $expected = [$entity2, $entity3, $entity5, $entity4];

        self::assertSame($expected, $actual);
    }

    public function testApplyingEvents(): void
    {
        $entity5 = new EventSourcedEntityStub(new EventSourcedEntityStubId('a1880189-03a6-43aa-963f-610002a77db5'));
        $entity4 = new EventSourcedEntityStub(new EventSourcedEntityStubId('a1880189-03a6-43aa-963f-610002a77db4'));
        $entity3 = new EventSourcedEntityStub(new EventSourcedEntityStubId('a1880189-03a6-43aa-963f-610002a77db3'));
        $entity2 = new EventSourcedEntityStub(new EventSourcedEntityStubId('a1880189-03a6-43aa-963f-610002a77db2'), $entity3, $entity5);
        $entity1 = new EventSourcedEntityStub(new EventSourcedEntityStubId('a1880189-03a6-43aa-963f-610002a77db1'), $entity2, $entity4);

        $event1 = new Event\Sourced\Entity\HelperTest\EventSourcedEntityStubEvent1();
        $event1 = Event\Envelope::new($event1, $entity1->id());
        $event2 = new Event\Sourced\Entity\HelperTest\EventSourcedEntityStubEvent2();
        $event2 = Event\Envelope::new($event2, $entity1->id());
        $event3 = new Event\Sourced\Entity\HelperTest\EventSourcedEntityStubEvent3();
        $event3 = Event\Envelope::new($event3, $entity1->id());
        $event4 = new Event\Sourced\Entity\HelperTest\EventSourcedEntityStubEvent4();
        $event4 = Event\Envelope::new($event4, $entity1->id());

        $helper = Helper::for($entity1);

        $helper->applyEvent($event1);

        self::assertEquals([$event1->message()], $entity1->appliedEvents());
        self::assertEquals([], $entity2->appliedEvents());
        self::assertEquals([], $entity3->appliedEvents());
        self::assertEquals([], $entity4->appliedEvents());
        self::assertEquals([], $entity5->appliedEvents());

        $helper->applyEvent($event2);

        self::assertEquals([$event1->message(), $event2->message()], $entity1->appliedEvents());
        self::assertEquals([], $entity2->appliedEvents());
        self::assertEquals([], $entity3->appliedEvents());
        self::assertEquals([], $entity4->appliedEvents());
        self::assertEquals([], $entity5->appliedEvents());

        try {
            $helper->applyEvent($event3);
            self::fail();
        } catch (Event\Exception\NoEventApplyingMethodFound $exception) {
            self::assertSame($event3, $exception->event());
            self::assertSame($entity1, $exception->object());
        }

        self::assertEquals([$event1->message(), $event2->message()], $entity1->appliedEvents());
        self::assertEquals([], $entity2->appliedEvents());
        self::assertEquals([], $entity3->appliedEvents());
        self::assertEquals([], $entity4->appliedEvents());
        self::assertEquals([], $entity5->appliedEvents());

        $this->expectExceptionObject(new Event\Exception\TooManyEventApplyingMethodsFound($entity1, $event4));

        try {
            $helper->applyEvent($event4);
            self::fail();
        } catch (Event\Exception\TooManyEventApplyingMethodsFound $exception) {
            self::assertSame($event4, $exception->event());
            self::assertSame($entity1, $exception->object());
            self::assertEquals([$event1->message(), $event2->message()], $entity1->appliedEvents());
            self::assertEquals([], $entity2->appliedEvents());
            self::assertEquals([], $entity3->appliedEvents());
            self::assertEquals([], $entity4->appliedEvents());
            self::assertEquals([], $entity5->appliedEvents());

            throw $exception;
        }
    }
}

namespace Streak\Domain\Event\Sourced\Entity\HelperTest;

use Streak\Domain\Entity;
use Streak\Domain\Event;
use Streak\Domain\Id\UUID;

final class EventSourcedEntityStubId extends UUID implements Entity\Id
{
}

final class EventSourcedEntityStub implements Event\Sourced\Entity
{
    use Entity\Comparison;

    private self $self;
    private int $integer;
    private string $string;
    private object $object;
    private array $arrayOfIntegers;
    private array $arrayOfStrings;
    private array $arrayOfObjects;
    private array $arrayOfMixed;
    private string $nonInitializedString;
    private int $nonInitializedInteger;
    private object $nonInitializedObject;
    private array $nonInitializedArray;

    /**
     * @var Event\Envelope[]
     */
    private array $appliedEvents = [];

    public function __construct(private EventSourcedEntityStubId $id, private ?self $entity = null, ?self $entity2 = null)
    {
        $this->self = $this;
        $this->integer = 1;
        $this->string = 'string';
        $this->object = new \stdClass();
        $this->arrayOfIntegers = [-1, 0, 1];
        $this->arrayOfStrings = ['string 1', 'string 2'];
        $this->arrayOfObjects = [new \stdClass(), new \stdClass()];
        $this->arrayOfMixed = [-1, 0, 1, 'string 1', 'string 2', new \stdClass(), new \stdClass(), $this, $entity, $entity2, null];
    }

    public function id(): Entity\Id
    {
        return $this->id;
    }

    public function appliedEvents(): array
    {
        return $this->appliedEvents;
    }

    public function applyEvent(Event\Envelope $event): void
    {
        throw new \RuntimeException(__METHOD__ . ' should not be invoked.');
    }

    public function registerAggregateRoot(Event\Sourced\AggregateRoot $aggregate): void
    {
        throw new \RuntimeException(__METHOD__ . ' should not be invoked.');
    }

    public function registerAggregate(Event\Sourced\Aggregate $aggregate): void
    {
        throw new \RuntimeException(__METHOD__ . ' should not be invoked.');
    }

    public function aggregateRoot(): Event\Sourced\AggregateRoot
    {
        throw new \RuntimeException(__METHOD__ . ' should not be invoked.');
    }

    public function aggregate(): ?Event\Sourced\Aggregate
    {
        throw new \RuntimeException(__METHOD__ . ' should not be invoked.');
    }

    private function applyEventSourcedEntityStubEvent1(EventSourcedEntityStubEvent1 $event): bool
    {
        $this->appliedEvents[] = $event;

        return true;
    }

    private function applyEventSourcedEntityStubEvent2(EventSourcedEntityStubEvent2 $event): bool
    {
        $this->appliedEvents[] = $event;

        return true;
    }

    private function applyEventSourcedEntityStubEvent4a(EventSourcedEntityStubEvent4 $event): bool
    {
        throw new \RuntimeException(__METHOD__ . ' should not be invoked.');
    }

    private function applyEventSourcedEntityStubEvent4b(EventSourcedEntityStubEvent4 $event): bool
    {
        throw new \RuntimeException(__METHOD__ . ' should not be invoked.');
    }

    private function applyTwoArgumentsAtOnce(int $arg1, int $arg2): bool
    {
        throw new \RuntimeException(__METHOD__ . ' should not be invoked.');
    }

    private function applyNonRequiredArguments(int $arg1 = null): bool
    {
        throw new \RuntimeException(__METHOD__ . ' should not be invoked.');
    }

    private function applyMixedArguments($arg1): bool
    {
        throw new \RuntimeException(__METHOD__ . ' should not be invoked.');
    }
}

class EventSourcedEntityStubEvent1 implements Event
{
}

class EventSourcedEntityStubEvent2 implements Event
{
}

class EventSourcedEntityStubEvent3 implements Event
{
}
class EventSourcedEntityStubEvent4 implements Event
{
}
