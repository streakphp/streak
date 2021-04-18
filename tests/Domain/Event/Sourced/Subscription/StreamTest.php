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

namespace Streak\Domain\Event\Sourced\Subscription;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionListenedToEvent;
use Streak\Domain\Event\Sourced\Subscription\StreamTest\IterableStream;
use Streak\Domain\Id\UUID;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Sourced\Subscription\Stream
 */
class StreamTest extends TestCase
{
    private IterableStream $stream;

    private Event\Envelope $event1;
    private Event\Envelope $event2;
    private Event\Envelope $event3;
    private Event\Envelope $event4;

    protected function setUp(): void
    {
        $this->stream = $this->getMockBuilder(IterableStream::class)->getMock();

        $this->event1 = Event\Envelope::new($this->getMockBuilder(Event::class)->getMockForAbstractClass(), UUID::random());
        $this->event2 = Event\Envelope::new($this->getMockBuilder(Event::class)->getMockForAbstractClass(), UUID::random());
        $this->event3 = Event\Envelope::new($this->getMockBuilder(Event::class)->getMockForAbstractClass(), UUID::random());
        $this->event4 = Event\Envelope::new($this->getMockBuilder(Event::class)->getMockForAbstractClass(), UUID::random());
    }

    public function testStream(): void
    {
        $event2 = new SubscriptionListenedToEvent($this->event2, new \DateTimeImmutable());
        $event2 = Event\Envelope::new($event2, UUID::random());
        $event4 = new SubscriptionListenedToEvent($this->event4, new \DateTimeImmutable());
        $event4 = Event\Envelope::new($event4, UUID::random());

        $this->isIteratorFor($this->stream, [$this->event1, $event2, $this->event3, $event4]);

        $stream = new Stream($this->stream);

        self::assertEquals([$this->event2, $this->event4], iterator_to_array($stream));
    }

    public function testEmpty(): void
    {
        $this->isIteratorFor($this->stream, []);

        $this->stream
            ->expects(self::exactly(2))
            ->method('empty')
            ->with()
            ->willReturnOnConsecutiveCalls(
                true,
                false
            )
        ;

        $stream = new Stream($this->stream);

        self::assertTrue($stream->empty());
        self::assertFalse($stream->empty());
    }

    public function testEmptyStream(): void
    {
        $this->isIteratorFor($this->stream, []);

        $stream = new Stream($this->stream);

        self::assertEquals([], iterator_to_array($stream));
    }

    public function testFrom(): void
    {
        $this->isIteratorFor($this->stream, []);

        $this->expectExceptionObject(new \BadMethodCallException('Method not supported.'));

        $stream = new Stream($this->stream);
        $stream->from($this->event1);
    }

    public function testTo(): void
    {
        $this->isIteratorFor($this->stream, []);

        $this->expectExceptionObject(new \BadMethodCallException('Method not supported.'));

        $stream = new Stream($this->stream);
        $stream->to($this->event1);
    }

    public function testAfter(): void
    {
        $this->isIteratorFor($this->stream, []);

        $this->expectExceptionObject(new \BadMethodCallException('Method not supported.'));

        $stream = new Stream($this->stream);
        $stream->after($this->event1);
    }

    public function testBefore(): void
    {
        $this->isIteratorFor($this->stream, []);

        $this->expectExceptionObject(new \BadMethodCallException('Method not supported.'));

        $stream = new Stream($this->stream);
        $stream->before($this->event1);
    }

    public function testLimit(): void
    {
        $this->isIteratorFor($this->stream, []);

        $this->expectExceptionObject(new \BadMethodCallException('Method not supported.'));

        $stream = new Stream($this->stream);
        $stream->limit(1);
    }

    public function testOnly(): void
    {
        $this->isIteratorFor($this->stream, []);

        $this->expectExceptionObject(new \BadMethodCallException('Method not supported.'));

        $stream = new Stream($this->stream);
        $stream->only('event1', 'event2');
    }

    public function testWithout(): void
    {
        $this->isIteratorFor($this->stream, []);

        $this->expectExceptionObject(new \BadMethodCallException('Method not supported.'));

        $stream = new Stream($this->stream);
        $stream->without('event1', 'event2');
    }

    public function testFirst(): void
    {
        $this->isIteratorFor($this->stream, []);

        $this->expectExceptionObject(new \BadMethodCallException('Method not supported.'));

        $stream = new Stream($this->stream);
        $stream->first();
    }

    public function testLast(): void
    {
        $this->isIteratorFor($this->stream, []);

        $this->expectExceptionObject(new \BadMethodCallException('Method not supported.'));

        $stream = new Stream($this->stream);
        $stream->last();
    }

    private function isIteratorFor(MockObject $iterator, array $items)
    {
        $internal = new \ArrayIterator($items);

        $iterator
            ->method('getIterator')
            ->willReturn($internal)
        ;

        return $iterator;
    }
}

namespace Streak\Domain\Event\Sourced\Subscription\StreamTest;

use Streak\Domain\Event;

abstract class IterableStream implements Event\Stream, \IteratorAggregate
{
}
