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

namespace Streak\Infrastructure\Domain\Event;

use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\Id\UUID;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\Event\InMemoryStream
 */
class InMemoryStreamTest extends TestCase
{
    private Event\Envelope $event1;
    private Event\Envelope $event2;
    private Event\Envelope $event3;
    private Event\Envelope $event4;

    protected function setUp(): void
    {
        $this->event1 = Event\Envelope::new($this->getMockBuilder(Event::class)->getMockForAbstractClass(), UUID::random(), 1);
        $this->event2 = Event\Envelope::new($this->getMockBuilder(Event::class)->getMockForAbstractClass(), UUID::random(), 1);
        $this->event3 = Event\Envelope::new($this->getMockBuilder(Event::class)->getMockForAbstractClass(), UUID::random(), 1);
        $this->event4 = Event\Envelope::new($this->getMockBuilder(Event::class)->getMockForAbstractClass(), UUID::random(), 1);
    }

    public function testStream(): void
    {
        $empty1 = new InMemoryStream();

        self::assertTrue($empty1->empty());
        self::assertNull($empty1->first());
        self::assertNull($empty1->last());
        self::assertCount(0, $empty1);
        self::assertEmpty(iterator_to_array($empty1));

        $empty2 = $empty1->before($this->event1);

        self::assertNotSame($empty1, $empty2);
        self::assertTrue($empty2->empty());
        self::assertNull($empty2->first());
        self::assertNull($empty2->last());
        self::assertCount(0, $empty2);
        self::assertEmpty(iterator_to_array($empty2));

        $empty3 = $empty1->after($this->event4);

        self::assertNotSame($empty1, $empty3);
        self::assertTrue($empty3->empty());
        self::assertNull($empty3->first());
        self::assertNull($empty3->last());
        self::assertCount(0, $empty3);
        self::assertEmpty(iterator_to_array($empty3));

        $empty4 = $empty1->from($this->event1);

        self::assertNotSame($empty1, $empty4);
        self::assertTrue($empty4->empty());
        self::assertNull($empty4->first());
        self::assertNull($empty4->last());
        self::assertCount(0, $empty4);
        self::assertEmpty(iterator_to_array($empty4));

        $empty5 = $empty1->to($this->event4);

        self::assertNotSame($empty1, $empty5);
        self::assertTrue($empty5->empty());
        self::assertNull($empty5->first());
        self::assertNull($empty5->last());
        self::assertCount(0, $empty5);
        self::assertEmpty(iterator_to_array($empty5));

        $stream = new InMemoryStream($this->event1, $this->event2, $this->event3, $this->event4);

        self::assertFalse($stream->empty());
        self::assertSame($this->event1, $stream->first());
        self::assertSame($this->event4, $stream->last());
        self::assertCount(4, $stream);
        self::assertEquals([$this->event1, $this->event2, $this->event3, $this->event4], iterator_to_array($stream));

        $filtered1 = $stream->from($this->event1);

        self::assertNotSame($stream, $filtered1);
        self::assertFalse($filtered1->empty());
        self::assertSame($this->event1, $filtered1->first());
        self::assertSame($this->event4, $filtered1->last());
        self::assertCount(4, $filtered1);
        self::assertEquals([$this->event1, $this->event2, $this->event3, $this->event4], iterator_to_array($filtered1));

        $filtered2 = $stream->from($this->event2);

        self::assertNotSame($stream, $filtered2);
        self::assertFalse($filtered2->empty());
        self::assertSame($this->event2, $filtered2->first());
        self::assertSame($this->event4, $filtered2->last());
        self::assertCount(3, $filtered2);
        self::assertEquals([$this->event2, $this->event3, $this->event4], iterator_to_array($filtered2));

        $filtered3 = $stream->to($this->event4);

        self::assertNotSame($stream, $filtered3);
        self::assertFalse($filtered3->empty());
        self::assertSame($this->event1, $filtered3->first());
        self::assertSame($this->event4, $filtered3->last());
        self::assertCount(4, $filtered3);
        self::assertEquals([$this->event1, $this->event2, $this->event3, $this->event4], iterator_to_array($filtered3));

        $filtered4 = $stream->to($this->event3);

        self::assertNotSame($stream, $filtered4);
        self::assertFalse($filtered4->empty());
        self::assertSame($this->event1, $filtered4->first());
        self::assertSame($this->event3, $filtered4->last());
        self::assertCount(3, $filtered4);
        self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($filtered4));

        $filtered5 = $stream->from($this->event2)->to($this->event3);

        self::assertNotSame($stream, $filtered5);
        self::assertFalse($filtered5->empty());
        self::assertSame($this->event2, $filtered5->first());
        self::assertSame($this->event3, $filtered5->last());
        self::assertCount(2, $filtered5);
        self::assertEquals([$this->event2, $this->event3], iterator_to_array($filtered5));

        $filtered6 = $stream->after($this->event1);

        self::assertNotSame($stream, $filtered6);
        self::assertFalse($filtered6->empty());
        self::assertSame($this->event2, $filtered6->first());
        self::assertSame($this->event4, $filtered6->last());
        self::assertCount(3, $filtered6);
        self::assertEquals([$this->event2, $this->event3, $this->event4], iterator_to_array($filtered6));

        $filtered7 = $stream->before($this->event4);

        self::assertNotSame($stream, $filtered7);
        self::assertFalse($filtered7->empty());
        self::assertSame($this->event1, $filtered7->first());
        self::assertSame($this->event3, $filtered7->last());
        self::assertCount(3, $filtered7);
        self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($filtered7));

        $empty6 = $stream->before($this->event1);

        self::assertNotSame($stream, $empty6);
        self::assertTrue($empty6->empty());
        self::assertNull($empty6->first());
        self::assertNull($empty6->last());
        self::assertCount(0, $empty6);
        self::assertEmpty(iterator_to_array($empty6));

        $empty7 = $stream->after($this->event4);

        self::assertNotSame($stream, $empty7);
        self::assertTrue($empty7->empty());
        self::assertNull($empty7->first());
        self::assertNull($empty7->last());
        self::assertCount(0, $empty7);
        self::assertEmpty(iterator_to_array($empty7));

        $filtered8 = $stream->limit(2);

        self::assertFalse($filtered8->empty());
        self::assertSame($this->event1, $filtered8->first());
        self::assertSame($this->event2, $filtered8->last());
        self::assertCount(2, $filtered8);
        self::assertEquals([$this->event1, $this->event2], iterator_to_array($filtered8));

        $filtered9 = $stream->limit(100);

        self::assertFalse($filtered9->empty());
        self::assertSame($this->event1, $filtered9->first());
        self::assertSame($this->event4, $filtered9->last());
        self::assertCount(4, $filtered9);
        self::assertEquals([$this->event1, $this->event2, $this->event3, $this->event4], iterator_to_array($filtered9));
    }
}
