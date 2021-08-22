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

namespace Streak\Infrastructure\Domain\EventStore;

use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\EventStore;
use Streak\Domain\Exception\ConcurrentWriteDetected;
use Streak\Domain\Exception\EventAlreadyInStore;
use Streak\Domain\Id;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
abstract class EventStoreTestCase extends TestCase
{
    private EventStore $store;

    protected function setUp(): void
    {
        $this->store = $this->newEventStore();
    }

    public function testObject(): void
    {
        $producerId11 = new EventStoreTestCase\ProducerId1('producer1');
        $producerId12 = new EventStoreTestCase\ProducerId1('producer2');
        $producerId21 = new EventStoreTestCase\ProducerId2('producer1');

        $event111 = new EventStoreTestCase\Event1();
        $event111 = Event\Envelope::new($event111, $producerId11, 1);
        $event112 = new EventStoreTestCase\Event2();
        $event112 = Event\Envelope::new($event112, $producerId11, 2);
        $event113 = new EventStoreTestCase\Event3();
        $event113 = Event\Envelope::new($event113, $producerId11, 3);
        $event114 = new EventStoreTestCase\Event4();
        $event114 = Event\Envelope::new($event114, $producerId11, 4);

        $event121 = new EventStoreTestCase\Event1();
        $event121 = Event\Envelope::new($event121, $producerId12, 1);
        $event122 = new EventStoreTestCase\Event2();
        $event122 = Event\Envelope::new($event122, $producerId12, 2);
        $event123 = new EventStoreTestCase\Event3();
        $event123 = Event\Envelope::new($event123, $producerId12, 3);
        $event124 = new EventStoreTestCase\Event4();
        $event124 = Event\Envelope::new($event124, $producerId12, 4);

        $event211 = new EventStoreTestCase\Event1();
        $event211 = Event\Envelope::new($event211, $producerId21, 1);
        $event212 = new EventStoreTestCase\Event2();
        $event212 = Event\Envelope::new($event212, $producerId21, 2);
        $event213 = new EventStoreTestCase\Event3();
        $event213 = Event\Envelope::new($event213, $producerId21, 3);
        $event214 = new EventStoreTestCase\Event4();
        $event214 = Event\Envelope::new($event214, $producerId21, 4);

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producerId11));
        self::assertEquals([], iterator_to_array($stream));
        self::assertTrue($stream->empty());
        self::assertNull($stream->first());
        self::assertNull($stream->last());

        $this->store->add();

        $second = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producerId11));
        self::assertNotSame($stream, $second);

        self::assertEquals(iterator_to_array($stream), iterator_to_array($second));

        $this->store->add($event111, $event112);
        self::assertEquals([$event111, $event112], iterator_to_array($this->store->stream()));

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producerId11));
        self::assertEquals([$event111, $event112], iterator_to_array($stream));
        self::assertFalse($stream->empty());
        self::assertEquals($event111, $stream->first());
        self::assertEquals($event112, $stream->last());

        $second = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producerId11));
        self::assertNotSame($stream, $second);

        $this->store->add($event113, $event114);
        self::assertEquals([$event111, $event112, $event113, $event114], iterator_to_array($this->store->stream()));

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producerId11));
        self::assertEquals([$event111, $event112, $event113, $event114], iterator_to_array($stream));
        self::assertFalse($stream->empty());
        self::assertEquals($event111, $stream->first());
        self::assertEquals($event114, $stream->last());

        $second = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producerId11));
        self::assertNotSame($stream, $second);

        $stream = $stream->only(EventStoreTestCase\Event1::class, EventStoreTestCase\Event4::class);
        self::assertEquals([$event111, $event114], iterator_to_array($stream));
        self::assertFalse($stream->empty());
        self::assertEquals($event111, $stream->first());
        self::assertEquals($event114, $stream->last());

        $stream = $stream->without(EventStoreTestCase\Event4::class);
        self::assertEquals([$event111, $event112, $event113], iterator_to_array($stream));
        self::assertFalse($stream->empty());
        self::assertEquals($event111, $stream->first());
        self::assertEquals($event113, $stream->last());

        $stream = $stream->without(EventStoreTestCase\Event1::class);
        self::assertEquals([$event112, $event113, $event114], iterator_to_array($stream));
        self::assertFalse($stream->empty());
        self::assertEquals($event112, $stream->first());
        self::assertEquals($event114, $stream->last());

        $stream = $stream->without(EventStoreTestCase\Event1::class, EventStoreTestCase\Event2::class, EventStoreTestCase\Event3::class, EventStoreTestCase\Event4::class);
        self::assertEquals([], iterator_to_array($stream));
        self::assertTrue($stream->empty());
        self::assertNull($stream->first());
        self::assertNull($stream->last());

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producerId12));
        self::assertEquals([], iterator_to_array($stream));
        self::assertTrue($stream->empty());
        self::assertNull($stream->first());
        self::assertNull($stream->last());

        $second = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producerId12));
        self::assertNotSame($stream, $second);

        $this->store->add($event121, $event122, $event123, $event124);
        self::assertEquals([$event111, $event112, $event113, $event114, $event121, $event122, $event123, $event124], iterator_to_array($this->store->stream()));

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producerId11));
        self::assertEquals([$event111, $event112, $event113, $event114], iterator_to_array($stream));
        self::assertFalse($stream->empty());
        self::assertEquals($event111, $stream->first());
        self::assertEquals($event114, $stream->last());

        $second = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producerId11));
        self::assertNotSame($stream, $second);

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producerId12));
        self::assertEquals([$event121, $event122, $event123, $event124], iterator_to_array($stream));
        self::assertFalse($stream->empty());
        self::assertEquals($event121, $stream->first());
        self::assertEquals($event124, $stream->last());

        $second = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producerId12));
        self::assertNotSame($stream, $second);

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producerId11));
        self::assertEquals([$event111, $event112, $event113, $event114], iterator_to_array($stream));
        self::assertFalse($stream->empty());
        self::assertEquals($event111, $stream->first());
        self::assertEquals($event114, $stream->last());

        $filtered1 = $stream->from($event112)->to($event113);
        self::assertNotSame($stream, $filtered1);
        self::assertEquals([$event112, $event113], iterator_to_array($filtered1));
        self::assertFalse($filtered1->empty());
        self::assertEquals($event112, $filtered1->first());
        self::assertEquals($event113, $filtered1->last());

        $filtered2 = $stream->after($event112)->before($event113);
        self::assertNotSame($stream, $filtered2);
        self::assertEquals([], iterator_to_array($filtered2));
        self::assertTrue($filtered2->empty());
        self::assertNull($filtered2->first());
        self::assertNull($filtered2->last());

        $filtered3 = $stream->limit(3);
        self::assertNotSame($stream, $filtered3);
        self::assertEquals([$event111, $event112, $event113], iterator_to_array($filtered3));
        self::assertFalse($filtered3->empty());
        self::assertEquals($event111, $filtered3->first());
        self::assertEquals($event113, $filtered3->last());

        $filtered4 = $stream->limit(100);
        self::assertNotSame($stream, $filtered4);
        self::assertEquals([$event111, $event112, $event113, $event114], iterator_to_array($filtered4));
        self::assertFalse($filtered4->empty());
        self::assertEquals($event111, $filtered4->first());
        self::assertEquals($event114, $filtered4->last());

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producerId11, $producerId12));
        self::assertEquals([$event111, $event112, $event113, $event114, $event121, $event122, $event123, $event124], iterator_to_array($stream));
        self::assertFalse($stream->empty());
        self::assertEquals($event111, $stream->first());
        self::assertEquals($event124, $stream->last());

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerTypes(EventStoreTestCase\ProducerId1::class));
        self::assertEquals([$event111, $event112, $event113, $event114, $event121, $event122, $event123, $event124], iterator_to_array($stream));
        self::assertFalse($stream->empty());
        self::assertEquals($event111, $stream->first());
        self::assertEquals($event124, $stream->last());

        $this->store->add($event211, $event212, $event213, $event214);

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producerId21));
        self::assertEquals([$event211, $event212, $event213, $event214], iterator_to_array($stream));
        self::assertFalse($stream->empty());
        self::assertEquals($event211, $stream->first());
        self::assertEquals($event214, $stream->last());

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerTypes(EventStoreTestCase\ProducerId1::class));
        self::assertEquals([$event111, $event112, $event113, $event114, $event121, $event122, $event123, $event124], iterator_to_array($stream));
        self::assertFalse($stream->empty());
        self::assertEquals($event111, $stream->first());
        self::assertEquals($event124, $stream->last());

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producerId11, $producerId12));
        self::assertEquals([$event111, $event112, $event113, $event114, $event121, $event122, $event123, $event124], iterator_to_array($stream));
        self::assertFalse($stream->empty());
        self::assertEquals($event111, $stream->first());
        self::assertEquals($event124, $stream->last());

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerTypes(EventStoreTestCase\ProducerId2::class));
        self::assertEquals([$event211, $event212, $event213, $event214], iterator_to_array($stream));
        self::assertFalse($stream->empty());
        self::assertEquals($event211, $stream->first());
        self::assertEquals($event214, $stream->last());

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producerId21));
        self::assertEquals([$event211, $event212, $event213, $event214], iterator_to_array($stream));
        self::assertFalse($stream->empty());
        self::assertEquals($event211, $stream->first());
        self::assertEquals($event214, $stream->last());

        $stream = $this->store->stream();
        self::assertEquals([$event111, $event112, $event113, $event114, $event121, $event122, $event123, $event124, $event211, $event212, $event213, $event214], iterator_to_array($stream));
        self::assertFalse($stream->empty());
        self::assertEquals($event111, $stream->first());
        self::assertEquals($event214, $stream->last());

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerTypes(EventStoreTestCase\ProducerId1::class, EventStoreTestCase\ProducerId2::class));
        self::assertEquals([$event111, $event112, $event113, $event114, $event121, $event122, $event123, $event124, $event211, $event212, $event213, $event214], iterator_to_array($stream));
        self::assertFalse($stream->empty());
        self::assertEquals($event111, $stream->first());
        self::assertEquals($event214, $stream->last());
    }

    public function testConcurrentWriting(): void
    {
        $producerId1 = new EventStoreTestCase\ProducerId1('producer1');
        $event1 = new EventStoreTestCase\Event1();
        $event1 = Event\Envelope::new($event1, $producerId1, 1);
        $event2 = new EventStoreTestCase\Event2();
        $event2 = Event\Envelope::new($event2, $producerId1, 2);
        $event3 = new EventStoreTestCase\Event3();
        $event3 = Event\Envelope::new($event3, $producerId1, 1);
        $event4 = new EventStoreTestCase\Event4();
        $event4 = Event\Envelope::new($event4, $producerId1, 2);

        $this->store->add($event1, $event2);

        try {
            $this->store->add($event3, $event4);
            self::fail();
        } catch (ConcurrentWriteDetected $e) {
            self::assertEquals(new ConcurrentWriteDetected($producerId1), $e);
            // test that no events were added
            self::assertEquals([$event1, $event2], iterator_to_array($this->store->stream()));
        }
    }

    public function testNoConcurrentWritingErrorForUnversionedEvents(): void
    {
        $producerId1 = new EventStoreTestCase\ProducerId1('producer1');
        $event1a = new EventStoreTestCase\Event1();
        $event1a = Event\Envelope::new($event1a, $producerId1, null);
        $event2a = new EventStoreTestCase\Event2();
        $event2a = Event\Envelope::new($event2a, $producerId1, null);
        $event1b = new EventStoreTestCase\Event1();
        $event1b = Event\Envelope::new($event1b, $producerId1, null);
        $event2b = new EventStoreTestCase\Event2();
        $event2b = Event\Envelope::new($event2b, $producerId1, null);

        $this->store->add($event1a, $event2a);
        $this->store->add($event1b, $event2b);

        self::assertEquals([$event1a, $event2a, $event1b, $event2b], iterator_to_array($this->store->stream()));
    }

    public function testEventAlreadyInStore(): void
    {
        $producerId1 = new EventStoreTestCase\ProducerId1('producer1');
        $event1 = new EventStoreTestCase\Event1();
        $event1 = Event\Envelope::new($event1, $producerId1, 1);
        $event2 = new EventStoreTestCase\Event2();
        $event2 = Event\Envelope::new($event2, $producerId1, 2);
        $event3 = new EventStoreTestCase\Event2();
        $event3 = Event\Envelope::new($event3, $producerId1, 3);

        $this->store->add($event1, $event2);

        try {
            $this->store->add($event2, $event3);
            self::fail();
        } catch (EventAlreadyInStore $e) {
            self::assertEquals(new EventAlreadyInStore($event2), $e);
            // test that no events were added
            self::assertEquals([$event1, $event2], iterator_to_array($this->store->stream()));
        }
    }

    public function testItGetsEvent(): void
    {
        $uuid1 = new Id\UUID('9fd724b5-2c55-44ae-a3eb-8cefc493b072');
        $uuid2 = new Id\UUID('5e04364e-4590-403b-9f8f-3ae14f6dcce6');

        $event = new EventStoreTestCase\Event1();
        $event = new Event\Envelope($uuid1, 'event1', $event, new EventStoreTestCase\ProducerId1('producer1'), new EventStoreTestCase\ProducerId1('producer1'), 1);

        $this->store->add($event);

        self::assertEquals($event, $this->store->event($uuid1));
        self::assertNull($this->store->event($uuid2));
    }

    abstract protected function newEventStore(): EventStore;
}

namespace Streak\Infrastructure\Domain\EventStore\EventStoreTestCase;

use Streak\Domain;

abstract class ValueId implements Domain\Id
{
    public function __construct(private string $value)
    {
    }

    public function equals(object $id): bool
    {
        if (!$id instanceof self) {
            return false;
        }

        return $this->value === $id->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public static function fromString(string $id): Domain\Id
    {
        return new static($id);
    }
}

class ProducerId1 extends ValueId
{
}

class ProducerId2 extends ValueId
{
}

class Event1 implements Domain\Event
{
}

class Event2 implements Domain\Event
{
}

class Event3 implements Domain\Event
{
}

class Event4 implements Domain\Event
{
}
