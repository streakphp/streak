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
use Streak\Domain\Id\UUID;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Sourced\Subscription\Stream
 */
class StreamTest extends TestCase
{
    /**
     * @var Event\Stream|\IteratorAggregate|MockObject
     */
    private $stream;

    /**
     * @var Event|MockObject
     */
    private $event1;

    /**
     * @var Event|MockObject
     */
    private $event2;

    /**
     * @var Event|MockObject
     */
    private $event3;

    /**
     * @var Event|MockObject
     */
    private $event4;

    protected function setUp()
    {
        $this->stream = $this->getMockBuilder([Event\Stream::class, \IteratorAggregate::class])->getMock();

        $this->event1 = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $this->event1 = Event\Envelope::new($this->event1, UUID::random());
        $this->event2 = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $this->event2 = Event\Envelope::new($this->event2, UUID::random());
        $this->event3 = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $this->event3 = Event\Envelope::new($this->event3, UUID::random());
        $this->event4 = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $this->event4 = Event\Envelope::new($this->event4, UUID::random());
    }

    public function testStream()
    {
        $event2 = new SubscriptionListenedToEvent($this->event2, new \DateTimeImmutable());
        $event2 = Event\Envelope::new($event2, UUID::random());
        $event4 = new SubscriptionListenedToEvent($this->event4, new \DateTimeImmutable());
        $event4 = Event\Envelope::new($event4, UUID::random());

        $this->isIteratorFor($this->stream, [$this->event1, $event2, $this->event3, $event4]);

        $stream = new Stream($this->stream);

        $this->assertEquals([$this->event2, $this->event4], iterator_to_array($stream));
    }

    public function testEmpty()
    {
        $this->isIteratorFor($this->stream, []);

        $this->stream
            ->expects($this->exactly(2))
            ->method('empty')
            ->with()
            ->willReturnOnConsecutiveCalls(
                true,
                false
            )
        ;

        $stream = new Stream($this->stream);

        $this->assertTrue($stream->empty());
        $this->assertFalse($stream->empty());
    }

    public function testEmptyStream()
    {
        $this->isIteratorFor($this->stream, []);

        $stream = new Stream($this->stream);

        $this->assertEquals([], iterator_to_array($stream));
    }

    public function testFrom()
    {
        $this->isIteratorFor($this->stream, []);

        $this->expectExceptionObject(new \BadMethodCallException('Method not supported.'));

        $stream = new Stream($this->stream);
        $stream->from($this->event1);
    }

    public function testTo()
    {
        $this->isIteratorFor($this->stream, []);

        $this->expectExceptionObject(new \BadMethodCallException('Method not supported.'));

        $stream = new Stream($this->stream);
        $stream->to($this->event1);
    }

    public function testAfter()
    {
        $this->isIteratorFor($this->stream, []);

        $this->expectExceptionObject(new \BadMethodCallException('Method not supported.'));

        $stream = new Stream($this->stream);
        $stream->after($this->event1);
    }

    public function testBefore()
    {
        $this->isIteratorFor($this->stream, []);

        $this->expectExceptionObject(new \BadMethodCallException('Method not supported.'));

        $stream = new Stream($this->stream);
        $stream->before($this->event1);
    }

    public function testLimit()
    {
        $this->isIteratorFor($this->stream, []);

        $this->expectExceptionObject(new \BadMethodCallException('Method not supported.'));

        $stream = new Stream($this->stream);
        $stream->limit(1);
    }

    public function testOnly()
    {
        $this->isIteratorFor($this->stream, []);

        $this->expectExceptionObject(new \BadMethodCallException('Method not supported.'));

        $stream = new Stream($this->stream);
        $stream->only('event1', 'event2');
    }

    public function testWithout()
    {
        $this->isIteratorFor($this->stream, []);

        $this->expectExceptionObject(new \BadMethodCallException('Method not supported.'));

        $stream = new Stream($this->stream);
        $stream->without('event1', 'event2');
    }

    public function testFirst()
    {
        $this->isIteratorFor($this->stream, []);

        $this->expectExceptionObject(new \BadMethodCallException('Method not supported.'));

        $stream = new Stream($this->stream);
        $stream->first();
    }

    public function testLast()
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
            ->expects($this->any())
            ->method('getIterator')
            ->willReturn($internal)
        ;

        return $iterator;
    }
}
