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

namespace Streak\Infrastructure\Domain\UnitOfWork;

use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\EventStore;
use Streak\Domain\Exception\ConcurrentWriteDetected;
use Streak\Domain\Id\UUID;
use Streak\Infrastructure\Domain\EventStoreUnitOfWorkTest\NonVersionableEventSourcedStub;
use Streak\Infrastructure\Domain\EventStoreUnitOfWorkTest\VersionableEventSourcedStub;
use Streak\Infrastructure\Domain\UnitOfWork\Exception\ObjectNotSupported;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\UnitOfWork\EventStoreUnitOfWork
 */
class EventStoreUnitOfWorkTest extends TestCase
{
    private EventStore $store;

    private Event $event1;
    private Event $event2;
    private Event $event3;
    private Event $event4;
    private Event $event5;

    protected function setUp(): void
    {
        $this->store = $this->getMockBuilder(EventStore::class)->getMockForAbstractClass();

        $this->event1 = $this->getMockBuilder(Event::class)->setMockClassName('event1')->getMockForAbstractClass();
        $this->event2 = $this->getMockBuilder(Event::class)->setMockClassName('event2')->getMockForAbstractClass();
        $this->event3 = $this->getMockBuilder(Event::class)->setMockClassName('event3')->getMockForAbstractClass();
        $this->event4 = $this->getMockBuilder(Event::class)->setMockClassName('event4')->getMockForAbstractClass();
        $this->event5 = $this->getMockBuilder(Event::class)->setMockClassName('event5')->getMockForAbstractClass();
    }

    public function testObject(): void
    {
        $id1 = UUID::random();
        $id2 = UUID::random();
        $id3 = UUID::random();
        $id4 = UUID::random();

        $event1 = Event\Envelope::new($this->event1, $id1, 1);
        $event2 = Event\Envelope::new($this->event2, $id2, 1);
        $event3 = Event\Envelope::new($this->event3, $id3, 1);
        $event4 = Event\Envelope::new($this->event4, $id3, 2);
        $event5 = Event\Envelope::new($this->event5, $id4, null);

        $object1 = new VersionableEventSourcedStub($id1, 1, $event1);
        $object2 = new VersionableEventSourcedStub($id2, 1, $event2);
        $object3 = new VersionableEventSourcedStub($id3, 2, $event3, $event4);
        $object4 = new NonVersionableEventSourcedStub($id4, $event5);

        $uow = new EventStoreUnitOfWork($this->store);

        self::assertEmpty($uow->uncommitted());
        self::assertEquals(0, $uow->count());
        self::assertFalse($uow->has($object1));
        self::assertFalse($uow->has($object2));
        self::assertFalse($uow->has($object3));
        self::assertFalse($uow->has($object4));

        $uow->remove($object1);

        self::assertEmpty($uow->uncommitted());
        self::assertEquals(0, $uow->count());
        self::assertFalse($uow->has($object1));
        self::assertFalse($uow->has($object2));
        self::assertFalse($uow->has($object3));
        self::assertFalse($uow->has($object4));

        $uow->add($object1);

        self::assertSame([$object1], $uow->uncommitted());
        self::assertEquals(1, $uow->count());
        self::assertTrue($uow->has($object1));
        self::assertFalse($uow->has($object2));
        self::assertFalse($uow->has($object3));
        self::assertFalse($uow->has($object4));

        $uow->add($object2);

        self::assertSame([$object1, $object2], $uow->uncommitted());
        self::assertEquals(2, $uow->count());
        self::assertTrue($uow->has($object1));
        self::assertTrue($uow->has($object2));
        self::assertFalse($uow->has($object3));
        self::assertFalse($uow->has($object4));

        $uow->remove($object2);

        self::assertSame([$object1], $uow->uncommitted());
        self::assertEquals(1, $uow->count());
        self::assertTrue($uow->has($object1));
        self::assertFalse($uow->has($object2));
        self::assertFalse($uow->has($object3));
        self::assertFalse($uow->has($object4));

        $uow->add($object3);

        self::assertSame([$object1, $object3], $uow->uncommitted());
        self::assertEquals(2, $uow->count());
        self::assertTrue($uow->has($object1));
        self::assertFalse($uow->has($object2));
        self::assertTrue($uow->has($object3));
        self::assertFalse($uow->has($object4));

        $uow->add($object4);

        self::assertSame([$object1, $object3, $object4], $uow->uncommitted());
        self::assertEquals(3, $uow->count());
        self::assertTrue($uow->has($object1));
        self::assertFalse($uow->has($object2));
        self::assertTrue($uow->has($object3));
        self::assertTrue($uow->has($object4));

        $this->store
            ->expects(self::at(0))
            ->method('add')
            ->with($event1)
        ;

        $this->store
            ->expects(self::at(1))
            ->method('add')
            ->with($event3, $event4)
        ;

        $this->store
            ->expects(self::at(2))
            ->method('add')
            ->with($event5)
        ;

        self::assertFalse($object1->commited());
        self::assertFalse($object2->commited());
        self::assertFalse($object3->commited());

        $commited = $uow->commit();
        $commited = iterator_to_array($commited);

        self::assertEmpty($uow->uncommitted());
        self::assertSame([$object1, $object3, $object4], $commited);
        self::assertTrue($object1->commited());
        self::assertFalse($object2->commited());
        self::assertTrue($object3->commited());
        self::assertEquals(0, $uow->count());
        self::assertFalse($uow->has($object1));
        self::assertFalse($uow->has($object2));
        self::assertFalse($uow->has($object3));
        self::assertFalse($uow->has($object4));
    }

    public function testError(): void
    {
        $id1 = UUID::random();
        $id2 = UUID::random();
        $id3 = UUID::random();

        $event1 = Event\Envelope::new($this->event1, $id1, 1);
        $event2 = Event\Envelope::new($this->event2, $id2, 1);
        $event3 = Event\Envelope::new($this->event3, $id3, 1);

        $object1 = new VersionableEventSourcedStub($id1, 1, $event1);
        $object2 = new VersionableEventSourcedStub($id2, 1, $event2);
        $object3 = new VersionableEventSourcedStub($id3, 1, $event3);

        $unknownError = new \RuntimeException();
//        $concurrencyError = new ConcurrentWriteDetected($id3);
        $concurrencyError = new ConcurrentWriteDetected(null);

        $uow = new EventStoreUnitOfWork($this->store);

        $uow->add($object1);
        $uow->add($object2);

        $this->store
            ->expects(self::at(0))
            ->method('add')
            ->with($event1)
            ->willThrowException($unknownError)
        ;

        $this->store
            ->expects(self::at(1))
            ->method('add')
            ->with($event1)
        ;

        $this->store
            ->expects(self::at(2))
            ->method('add')
            ->with($event2)
            ->willThrowException($unknownError)
        ;

        $this->store
            ->expects(self::at(3))
            ->method('add')
            ->with($event2)
        ;

        $this->store
            ->expects(self::at(4))
            ->method('add')
            ->with($event3)
            ->willThrowException($concurrencyError)
        ;

        try {
            iterator_to_array($uow->commit());
            self::fail();
        } catch (\RuntimeException $exception1) {
            self::assertSame($unknownError, $exception1);
            self::assertSame(2, $uow->count());
            self::assertTrue($uow->has($object1));
            self::assertTrue($uow->has($object2));
        }

        // retry
        try {
            iterator_to_array($uow->commit());
            self::fail();
        } catch (\RuntimeException $exception2) {
            self::assertSame($unknownError, $exception2);
            self::assertSame(1, $uow->count());
            self::assertFalse($uow->has($object1));
            self::assertTrue($uow->has($object2));
        }

        // retry
        try {
            iterator_to_array($uow->commit());
        } catch (\RuntimeException $exception3) {
            self::fail();
        } finally {
            self::assertSame(0, $uow->count());
            self::assertFalse($uow->has($object1));
            self::assertFalse($uow->has($object2));
        }

        $uow->add($object3);

        try {
            iterator_to_array($uow->commit());
            self::fail();
        } catch (ConcurrentWriteDetected $exception4) {
            self::assertSame(0, $uow->count());
            self::assertFalse($uow->has($object1));
            self::assertFalse($uow->has($object2));
            self::assertFalse($uow->has($object3)); // object was removed instead of saved for later retry
        }
    }

    public function testWrongObject(): void
    {
        $object = new \stdClass();

        $this->expectExceptionObject(new ObjectNotSupported($object));

        $uow = new EventStoreUnitOfWork($this->store);

        self::assertFalse($uow->has($object));

        $uow->remove($object);
        $uow->add($object);
    }
}

namespace Streak\Infrastructure\Domain\EventStoreUnitOfWorkTest;

use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Versionable;

class VersionableEventSourcedStub implements Event\Producer, Event\Consumer, Event\Replayable, Versionable
{
    private Domain\Id $id;
    private int $version;
    private array $events;
    private bool $commited = false;

    public function __construct(Domain\Id $id, int $version, Event\Envelope ...$events)
    {
        $this->id = $id;
        $this->version = $version;
        $this->events = $events;
    }

    public function equals(object $object): bool
    {
        throw new \BadMethodCallException();
    }

    public function id(): Domain\Id
    {
        return $this->id;
    }

    public function events(): array
    {
        return $this->events;
    }

    public function replay(Event\Stream $events): void
    {
        throw new \BadMethodCallException();
    }

    public function lastEvent(): ?Event\Envelope
    {
        throw new \BadMethodCallException();
    }

    public function applyEvent(Event\Envelope $event): void
    {
        throw new \BadMethodCallException();
    }

    public function version(): int
    {
        return $this->version;
    }

    public function commit(): void
    {
        $this->commited = true;
    }

    public function commited(): bool
    {
        return $this->commited;
    }
}

class NonVersionableEventSourcedStub implements Event\Producer, Event\Consumer, Event\Replayable
{
    private Domain\Id $id;
    private array $events;

    public function __construct(Domain\Id $id, Event\Envelope ...$events)
    {
        $this->id = $id;
        $this->events = $events;
    }

    public function equals(object $object): bool
    {
        throw new \BadMethodCallException();
    }

    public function id(): Domain\Id
    {
        return $this->id;
    }

    public function applyEvent(Event\Envelope $event): void
    {
        throw new \BadMethodCallException();
    }

    public function events(): array
    {
        return $this->events;
    }

    public function replay(Event\Stream $events): void
    {
        throw new \BadMethodCallException();
    }
}
