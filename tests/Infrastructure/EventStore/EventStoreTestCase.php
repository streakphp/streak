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

namespace Streak\Infrastructure\EventStore;

use PHPUnit\Framework\TestCase;
use Streak\Domain\Event\Metadata;
use Streak\Domain\EventStore;
use Streak\Domain\Exception\ConcurrentWriteDetected;
use Streak\Domain\Exception\EventAlreadyInStore;
use Streak\Domain\Exception\EventNotInStore;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
abstract class EventStoreTestCase extends TestCase
{
    /**
     * @var DbalPostgresEventStore
     */
    private $store;

    protected function setUp()
    {
        $this->store = $this->newEventStore();
    }

    public function testGettingProducerId()
    {
        $event = new EventStoreTestCase\Event1();

        $metadata = Metadata::fromObject($event);
        $metadata->set('producer_type', EventStoreTestCase\ProducerId1::class);
        $metadata->set('producer_id', 'uuid');
        $metadata->toObject($event);

        $id = $this->store->producerId($event);

        $this->assertEquals($id, EventStoreTestCase\ProducerId1::fromString('uuid'));
    }

    public function testGettingProducerIdForEventNotInStore()
    {
        $event = new EventStoreTestCase\Event1();

        $exception = new EventNotInStore($event);
        $this->expectExceptionObject($exception);

        $this->store->producerId($event);
    }

    public function testGettingProducerIdForEventWithInvalidMetadata()
    {
        $event = new EventStoreTestCase\Event1();

        $metadata = Metadata::fromObject($event);
        $metadata->set('producer_type', \stdClass::class);
        $metadata->set('producer_id', 'uuid');
        $metadata->toObject($event);

        $exception = new \InvalidArgumentException();
        $this->expectExceptionObject($exception);

        $this->store->producerId($event);
    }

    public function testObject()
    {
        $producer11 = new EventStoreTestCase\ProducerId1('producer1');
        $producer12 = new EventStoreTestCase\ProducerId1('producer2');
        $producer21 = new EventStoreTestCase\ProducerId2('producer1');

        $event111 = new EventStoreTestCase\Event1();
        $event112 = new EventStoreTestCase\Event2();
        $event113 = new EventStoreTestCase\Event3();
        $event114 = new EventStoreTestCase\Event4();

        $event121 = new EventStoreTestCase\Event1();
        $event122 = new EventStoreTestCase\Event2();
        $event123 = new EventStoreTestCase\Event3();
        $event124 = new EventStoreTestCase\Event4();

        $event211 = new EventStoreTestCase\Event1();
        $event212 = new EventStoreTestCase\Event2();
        $event213 = new EventStoreTestCase\Event3();
        $event214 = new EventStoreTestCase\Event4();

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producer11));
        $this->assertEquals([], iterator_to_array($stream));
        $this->assertTrue($stream->empty());
        $this->assertNull($stream->first());
        $this->assertNull($stream->last());

        $this->store->add($producer11, null);

        $second = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producer11));
        $this->assertNotSame($stream, $second);

        $this->assertEquals(iterator_to_array($stream), iterator_to_array($second));

        $this->store->add($producer11, 0, $event111, $event112);
        $this->assertEquals([$event111, $event112], iterator_to_array($this->store->stream()));

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producer11));
        $this->assertEquals([$event111, $event112], iterator_to_array($stream));
        $this->assertFalse($stream->empty());
        $this->assertEquals($event111, $stream->first());
        $this->assertEquals($event112, $stream->last());

        $second = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producer11));
        $this->assertNotSame($stream, $second);

        $this->store->add($producer11, 2, $event113, $event114);
        $this->assertEquals([$event111, $event112, $event113, $event114], iterator_to_array($this->store->stream()));

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producer11));
        $this->assertEquals([$event111, $event112, $event113, $event114], iterator_to_array($stream));
        $this->assertFalse($stream->empty());
        $this->assertEquals($event111, $stream->first());
        $this->assertEquals($event114, $stream->last());

        $second = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producer11));
        $this->assertNotSame($stream, $second);

        $stream = $stream->only(EventStoreTestCase\Event1::class, EventStoreTestCase\Event4::class);
        $this->assertEquals([$event111, $event114], iterator_to_array($stream));
        $this->assertFalse($stream->empty());
        $this->assertEquals($event111, $stream->first());
        $this->assertEquals($event114, $stream->last());

        $stream = $stream->without(EventStoreTestCase\Event4::class);
        $this->assertEquals([$event111, $event112, $event113], iterator_to_array($stream));
        $this->assertFalse($stream->empty());
        $this->assertEquals($event111, $stream->first());
        $this->assertEquals($event113, $stream->last());

        $stream = $stream->without(EventStoreTestCase\Event1::class);
        $this->assertEquals([$event112, $event113, $event114], iterator_to_array($stream));
        $this->assertFalse($stream->empty());
        $this->assertEquals($event112, $stream->first());
        $this->assertEquals($event114, $stream->last());

        $stream = $stream->without(EventStoreTestCase\Event1::class, EventStoreTestCase\Event2::class, EventStoreTestCase\Event3::class, EventStoreTestCase\Event4::class);
        $this->assertEquals([], iterator_to_array($stream));
        $this->assertTrue($stream->empty());
        $this->assertNull($stream->first());
        $this->assertNull($stream->last());

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producer12));
        $this->assertEquals([], iterator_to_array($stream));
        $this->assertTrue($stream->empty());
        $this->assertNull($stream->first());
        $this->assertNull($stream->last());

        $second = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producer12));
        $this->assertNotSame($stream, $second);

        $this->store->add($producer12, 0, $event121, $event122, $event123, $event124);
        $this->assertEquals([$event111, $event112, $event113, $event114, $event121, $event122, $event123, $event124], iterator_to_array($this->store->stream()));

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producer11));
        $this->assertEquals([$event111, $event112, $event113, $event114], iterator_to_array($stream));
        $this->assertFalse($stream->empty());
        $this->assertEquals($event111, $stream->first());
        $this->assertEquals($event114, $stream->last());

        $second = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producer11));
        $this->assertNotSame($stream, $second);

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producer12));
        $this->assertEquals([$event121, $event122, $event123, $event124], iterator_to_array($stream));
        $this->assertFalse($stream->empty());
        $this->assertEquals($event121, $stream->first());
        $this->assertEquals($event124, $stream->last());

        $second = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producer12));
        $this->assertNotSame($stream, $second);

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producer11));
        $this->assertEquals([$event111, $event112, $event113, $event114], iterator_to_array($stream));
        $this->assertFalse($stream->empty());
        $this->assertEquals($event111, $stream->first());
        $this->assertEquals($event114, $stream->last());

        $filtered1 = $stream->from($event112)->to($event113);
        $this->assertNotSame($stream, $filtered1);
        $this->assertEquals([$event112, $event113], iterator_to_array($filtered1));
        $this->assertFalse($filtered1->empty());
        $this->assertEquals($event112, $filtered1->first());
        $this->assertEquals($event113, $filtered1->last());

        $filtered2 = $stream->after($event112)->before($event113);
        $this->assertNotSame($stream, $filtered2);
        $this->assertEquals([], iterator_to_array($filtered2));
        $this->assertTrue($filtered2->empty());
        $this->assertEquals(null, $filtered2->first());
        $this->assertEquals(null, $filtered2->last());

        $filtered3 = $stream->limit(3);
        $this->assertNotSame($stream, $filtered3);
        $this->assertEquals([$event111, $event112, $event113], iterator_to_array($filtered3));
        $this->assertFalse($filtered3->empty());
        $this->assertEquals($event111, $filtered3->first());
        $this->assertEquals($event113, $filtered3->last());

        $filtered4 = $stream->limit(100);
        $this->assertNotSame($stream, $filtered4);
        $this->assertEquals([$event111, $event112, $event113, $event114], iterator_to_array($filtered4));
        $this->assertFalse($filtered4->empty());
        $this->assertEquals($event111, $filtered4->first());
        $this->assertEquals($event114, $filtered4->last());

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producer11, $producer12));
        $this->assertEquals([$event111, $event112, $event113, $event114, $event121, $event122, $event123, $event124], iterator_to_array($stream));
        $this->assertFalse($stream->empty());
        $this->assertEquals($event111, $stream->first());
        $this->assertEquals($event124, $stream->last());

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerTypes(EventStoreTestCase\ProducerId1::class));
        $this->assertEquals([$event111, $event112, $event113, $event114, $event121, $event122, $event123, $event124], iterator_to_array($stream));
        $this->assertFalse($stream->empty());
        $this->assertEquals($event111, $stream->first());
        $this->assertEquals($event124, $stream->last());

        $this->store->add($producer21, 0, $event211, $event212, $event213, $event214);

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producer21));
        $this->assertEquals([$event211, $event212, $event213, $event214], iterator_to_array($stream));
        $this->assertFalse($stream->empty());
        $this->assertEquals($event211, $stream->first());
        $this->assertEquals($event214, $stream->last());

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerTypes(EventStoreTestCase\ProducerId1::class));
        $this->assertEquals([$event111, $event112, $event113, $event114, $event121, $event122, $event123, $event124], iterator_to_array($stream));
        $this->assertFalse($stream->empty());
        $this->assertEquals($event111, $stream->first());
        $this->assertEquals($event124, $stream->last());

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producer11, $producer12));
        $this->assertEquals([$event111, $event112, $event113, $event114, $event121, $event122, $event123, $event124], iterator_to_array($stream));
        $this->assertFalse($stream->empty());
        $this->assertEquals($event111, $stream->first());
        $this->assertEquals($event124, $stream->last());

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerTypes(EventStoreTestCase\ProducerId2::class));
        $this->assertEquals([$event211, $event212, $event213, $event214], iterator_to_array($stream));
        $this->assertFalse($stream->empty());
        $this->assertEquals($event211, $stream->first());
        $this->assertEquals($event214, $stream->last());

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerIds($producer21));
        $this->assertEquals([$event211, $event212, $event213, $event214], iterator_to_array($stream));
        $this->assertFalse($stream->empty());
        $this->assertEquals($event211, $stream->first());
        $this->assertEquals($event214, $stream->last());

        $stream = $this->store->stream();
        $this->assertEquals([$event111, $event112, $event113, $event114, $event121, $event122, $event123, $event124, $event211, $event212, $event213, $event214], iterator_to_array($stream));
        $this->assertFalse($stream->empty());
        $this->assertEquals($event111, $stream->first());
        $this->assertEquals($event214, $stream->last());

        $stream = $this->store->stream(EventStore\Filter::nothing()->filterProducerTypes(EventStoreTestCase\ProducerId1::class, EventStoreTestCase\ProducerId2::class));
        $this->assertEquals([$event111, $event112, $event113, $event114, $event121, $event122, $event123, $event124, $event211, $event212, $event213, $event214], iterator_to_array($stream));
        $this->assertFalse($stream->empty());
        $this->assertEquals($event111, $stream->first());
        $this->assertEquals($event214, $stream->last());
    }

    public function testConcurrentWriting()
    {
        $event1 = new EventStoreTestCase\Event1();
        $event2 = new EventStoreTestCase\Event2();
        $event3 = new EventStoreTestCase\Event3();
        $event4 = new EventStoreTestCase\Event4();
        $producer = new EventStoreTestCase\ProducerId1('producer1');

        $this->store->add($producer, 0, $event1, $event2);

        $this->expectExceptionObject(new ConcurrentWriteDetected($producer));

        try {
            $this->store->add($producer, 0, $event3, $event4);
        } catch (ConcurrentWriteDetected $e) {
            // test that no events were added
            $this->assertEquals([$event1, $event2], iterator_to_array($this->store->stream()));

            throw $e;
        }
    }

    public function testNoConcurrentWritingErrorForUnversionedEvents()
    {
        $event1a = new EventStoreTestCase\Event1();
        $event2a = new EventStoreTestCase\Event2();
        $event1b = new EventStoreTestCase\Event1();
        $event2b = new EventStoreTestCase\Event2();
        $producer = new EventStoreTestCase\ProducerId1('producer1');

        $this->store->add($producer, null, $event1a, $event2a);
        $this->store->add($producer, null, $event1b, $event2b);

        $this->assertEquals([$event1a, $event2a, $event1b, $event2b], iterator_to_array($this->store->stream()));
    }

    public function testEventAlreadyInStore()
    {
        $event1 = new EventStoreTestCase\Event1();
        $event2 = new EventStoreTestCase\Event2();
        $event3 = new EventStoreTestCase\Event2();
        $producer = new EventStoreTestCase\ProducerId1('producer1');

        $this->store->add($producer, 0, $event1, $event2);

        $this->expectExceptionObject(new EventAlreadyInStore($event2));

        try {
            $this->store->add($producer, 2, $event2, $event3);
        } catch (EventAlreadyInStore $e) {
            // test that no events were added
            $this->assertEquals([$event1, $event2], iterator_to_array($this->store->stream()));

            throw $e;
        }
    }

    abstract protected function newEventStore() : EventStore;
}

namespace Streak\Infrastructure\EventStore\EventStoreTestCase;

use Streak\Domain;

abstract class ValueId implements Domain\Id
{
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function equals($id) : bool
    {
        if (!$id instanceof self) {
            return false;
        }

        return $this->value === $id->value;
    }

    public function toString() : string
    {
        return $this->value;
    }

    public static function fromString(string $id) : Domain\Id
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
