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

namespace Streak\Infrastructure\UnitOfWork;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\EventStore;
use Streak\Domain\Exception\ConcurrentWriteDetected;
use Streak\Domain\Id\UUID;
use Streak\Infrastructure\UnitOfWorkTest\NonVersionableEventSourcedStub;
use Streak\Infrastructure\UnitOfWorkTest\VersionableEventSourcedStub;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\UnitOfWork\EventStoreUnitOfWork
 */
class EventStoreUnitOfWorkTest extends TestCase
{
    /**
     * @var EventStore|MockObject
     */
    private $store;

    /**
     * @var Event\Sourced|MockObject
     */
    private $event1;

    /**
     * @var Event\Sourced|MockObject
     */
    private $event2;

    /**
     * @var Event\Sourced|MockObject
     */
    private $event3;

    /**
     * @var Event\Sourced|MockObject
     */
    private $event4;

    /**
     * @var Event\Sourced|MockObject
     */
    private $event5;

    public function setUp()
    {
        $this->store = $this->getMockBuilder(EventStore::class)->getMockForAbstractClass();

        $this->event1 = $this->getMockBuilder(Event::class)->setMockClassName('event1')->getMockForAbstractClass();
        $this->event2 = $this->getMockBuilder(Event::class)->setMockClassName('event2')->getMockForAbstractClass();
        $this->event3 = $this->getMockBuilder(Event::class)->setMockClassName('event3')->getMockForAbstractClass();
        $this->event4 = $this->getMockBuilder(Event::class)->setMockClassName('event4')->getMockForAbstractClass();
        $this->event5 = $this->getMockBuilder(Event::class)->setMockClassName('event5')->getMockForAbstractClass();
    }

    public function testObject()
    {
        $id1 = UUID::create();
        $id2 = UUID::create();
        $id3 = UUID::create();
        $id4 = UUID::create();

        $object1 = new VersionableEventSourcedStub($id1, 0, $this->event1);
        $object2 = new VersionableEventSourcedStub($id2, 1, $this->event2);
        $object3 = new VersionableEventSourcedStub($id3, 2, $this->event3, $this->event4);
        $object4 = new NonVersionableEventSourcedStub($id4, $this->event5);

        $uow = new EventStoreUnitOfWork($this->store);

        $this->assertEmpty($uow->uncommitted());
        $this->assertEquals(0, $uow->count());
        $this->assertFalse($uow->has($object1));
        $this->assertFalse($uow->has($object2));
        $this->assertFalse($uow->has($object3));
        $this->assertFalse($uow->has($object4));

        $uow->remove($object1);

        $this->assertEmpty($uow->uncommitted());
        $this->assertEquals(0, $uow->count());
        $this->assertFalse($uow->has($object1));
        $this->assertFalse($uow->has($object2));
        $this->assertFalse($uow->has($object3));
        $this->assertFalse($uow->has($object4));

        $uow->add($object1);

        $this->assertSame([$object1], $uow->uncommitted());
        $this->assertEquals(1, $uow->count());
        $this->assertTrue($uow->has($object1));
        $this->assertFalse($uow->has($object2));
        $this->assertFalse($uow->has($object3));
        $this->assertFalse($uow->has($object4));

        $uow->add($object2);

        $this->assertSame([$object1, $object2], $uow->uncommitted());
        $this->assertEquals(2, $uow->count());
        $this->assertTrue($uow->has($object1));
        $this->assertTrue($uow->has($object2));
        $this->assertFalse($uow->has($object3));
        $this->assertFalse($uow->has($object4));

        $uow->remove($object2);

        $this->assertSame([$object1], $uow->uncommitted());
        $this->assertEquals(1, $uow->count());
        $this->assertTrue($uow->has($object1));
        $this->assertFalse($uow->has($object2));
        $this->assertFalse($uow->has($object3));
        $this->assertFalse($uow->has($object4));

        $uow->add($object3);

        $this->assertSame([$object1, $object3], $uow->uncommitted());
        $this->assertEquals(2, $uow->count());
        $this->assertTrue($uow->has($object1));
        $this->assertFalse($uow->has($object2));
        $this->assertTrue($uow->has($object3));
        $this->assertFalse($uow->has($object4));

        $uow->add($object4);

        $this->assertSame([$object1, $object3, $object4], $uow->uncommitted());
        $this->assertEquals(3, $uow->count());
        $this->assertTrue($uow->has($object1));
        $this->assertFalse($uow->has($object2));
        $this->assertTrue($uow->has($object3));
        $this->assertTrue($uow->has($object4));

        $this->store
            ->expects($this->at(0))
            ->method('add')
            ->with($id1, 0, $this->event1)
        ;

        $this->store
            ->expects($this->at(1))
            ->method('add')
            ->with($id3, 2, $this->event3, $this->event4)
        ;

        $this->store
            ->expects($this->at(2))
            ->method('add')
            ->with($id4, null, $this->event5)
        ;

        $this->assertFalse($object1->commited());
        $this->assertFalse($object2->commited());
        $this->assertFalse($object3->commited());

        $commited = $uow->commit();
        $commited = iterator_to_array($commited);

        $this->assertEmpty($uow->uncommitted());
        $this->assertSame([$object1, $object3, $object4], $commited);
        $this->assertTrue($object1->commited());
        $this->assertFalse($object2->commited());
        $this->assertTrue($object3->commited());
        $this->assertEquals(0, $uow->count());
        $this->assertFalse($uow->has($object1));
        $this->assertFalse($uow->has($object2));
        $this->assertFalse($uow->has($object3));
        $this->assertFalse($uow->has($object4));
    }

    public function testError()
    {
        $id1 = UUID::create();
        $id2 = UUID::create();
        $id3 = UUID::create();
        $object1 = new VersionableEventSourcedStub($id1, 0, $this->event1);
        $object2 = new VersionableEventSourcedStub($id2, 0, $this->event2);
        $object3 = new VersionableEventSourcedStub($id3, 1, $this->event3);

        $unknownError = new \RuntimeException();
        $concurrencyError = new ConcurrentWriteDetected($id3);

        $uow = new EventStoreUnitOfWork($this->store);

        $uow->add($object1);
        $uow->add($object2);

        $this->store
            ->expects($this->at(0))
            ->method('add')
            ->with($id1, 0, $this->event1)
            ->willThrowException($unknownError)
        ;

        $this->store
            ->expects($this->at(1))
            ->method('add')
            ->with($id1, 0, $this->event1)
        ;

        $this->store
            ->expects($this->at(2))
            ->method('add')
            ->with($id2, 0, $this->event2)
            ->willThrowException($unknownError)
        ;

        $this->store
            ->expects($this->at(3))
            ->method('add')
            ->with($id2, 0, $this->event2)
        ;

        $this->store
            ->expects($this->at(4))
            ->method('add')
            ->with($id3, 1, $this->event3)
            ->willThrowException($concurrencyError)
        ;

        try {
            $commited = iterator_to_array($uow->commit());
        } catch (\RuntimeException $exception1) {
            $this->assertSame($unknownError, $exception1);
            $this->assertSame(2, $uow->count());
            $this->assertTrue($uow->has($object1));
            $this->assertTrue($uow->has($object2));
        } finally {
            $this->assertTrue(isset($exception1));
        }

        // retry
        try {
            $commited = iterator_to_array($uow->commit());
        } catch (\RuntimeException $exception2) {
            $this->assertSame($unknownError, $exception2);
            $this->assertSame(1, $uow->count());
            $this->assertFalse($uow->has($object1));
            $this->assertTrue($uow->has($object2));
        } finally {
            $this->assertTrue(isset($exception2));
        }

        // retry
        try {
            $commited = iterator_to_array($uow->commit());
        } catch (\RuntimeException $exception3) {
            $this->assertSame([$object1], $commited);
        } finally {
            $this->assertSame(0, $uow->count());
            $this->assertFalse($uow->has($object1));
            $this->assertFalse($uow->has($object2));
            $this->assertFalse(isset($exception3));
        }

        $uow->add($object3);

        try {
            $commited = iterator_to_array($uow->commit());
        } catch (ConcurrentWriteDetected $exception4) {
            $this->assertSame(0, $uow->count());
            $this->assertFalse($uow->has($object1));
            $this->assertFalse($uow->has($object2));
            $this->assertFalse($uow->has($object3)); // object was removed instead of saved for later retry
        } finally {
            $this->assertTrue(isset($exception4));
        }
    }
}

namespace Streak\Infrastructure\UnitOfWorkTest;

use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Versionable;

class VersionableEventSourcedStub implements Event\Sourced, Versionable
{
    private $id;
    private $version;
    private $events;
    private $commited = false;

    public function __construct(Domain\Id $id, int $version, Event ...$events)
    {
        $this->id = $id;
        $this->version = $version;
        $this->events = $events;
    }

    public function equals($object) : bool
    {
        throw new \BadMethodCallException();
    }

    public function lastReplayed() : ?Event
    {
        throw new \BadMethodCallException();
    }

    public function producerId() : Domain\Id
    {
        return $this->id;
    }

    public function events() : array
    {
        return $this->events;
    }

    public function replay(Event\Stream $events) : void
    {
        throw new \BadMethodCallException();
    }

    public function version() : int
    {
        return $this->version;
    }

    public function commit() : void
    {
        $this->commited = true;
    }

    public function commited() : bool
    {
        return $this->commited;
    }
}

class NonVersionableEventSourcedStub implements Event\Sourced
{
    private $id;
    private $events;

    public function __construct(Domain\Id $id, Event ...$events)
    {
        $this->id = $id;
        $this->events = $events;
    }

    public function equals($object) : bool
    {
        throw new \BadMethodCallException();
    }

    public function lastReplayed() : ?Event
    {
        throw new \BadMethodCallException();
    }

    public function producerId() : Domain\Id
    {
        return $this->id;
    }

    public function events() : array
    {
        return $this->events;
    }

    public function replay(Event\Stream $events) : void
    {
        throw new \BadMethodCallException();
    }
}
