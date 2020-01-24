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

namespace Streak\Infrastructure\Event;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\Id;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Event\InMemoryStream
 */
class InMemoryStreamTest extends TestCase
{
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
        $this->event1 = $this->getMockBuilder(Event::class)->setMockClassName('event1')->getMockForAbstractClass();
        $this->event1 = Event\Envelope::new($this->event1, Id\UUID::random(), 1);
        $this->event2 = $this->getMockBuilder(Event::class)->setMockClassName('event2')->getMockForAbstractClass();
        $this->event2 = Event\Envelope::new($this->event2, Id\UUID::random(), 1);
        $this->event3 = $this->getMockBuilder(Event::class)->setMockClassName('event3')->getMockForAbstractClass();
        $this->event3 = Event\Envelope::new($this->event3, Id\UUID::random(), 1);
        $this->event4 = $this->getMockBuilder(Event::class)->setMockClassName('event4')->getMockForAbstractClass();
        $this->event4 = Event\Envelope::new($this->event4, Id\UUID::random(), 1);
    }

    public function testStream()
    {
        $empty1 = new InMemoryStream();

        $this->assertTrue($empty1->empty());
        $this->assertNull($empty1->first());
        $this->assertNull($empty1->last());
        $this->assertEquals(0, iterator_count($empty1));
        $this->assertEmpty(iterator_to_array($empty1));

        $empty2 = $empty1->before($this->event1);

        $this->assertNotSame($empty1, $empty2);
        $this->assertTrue($empty2->empty());
        $this->assertNull($empty2->first());
        $this->assertNull($empty2->last());
        $this->assertEquals(0, iterator_count($empty2));
        $this->assertEmpty(iterator_to_array($empty2));

        $empty3 = $empty1->after($this->event4);

        $this->assertNotSame($empty1, $empty3);
        $this->assertTrue($empty3->empty());
        $this->assertNull($empty3->first());
        $this->assertNull($empty3->last());
        $this->assertEquals(0, iterator_count($empty3));
        $this->assertEmpty(iterator_to_array($empty3));

        $empty4 = $empty1->from($this->event1);

        $this->assertNotSame($empty1, $empty4);
        $this->assertTrue($empty4->empty());
        $this->assertNull($empty4->first());
        $this->assertNull($empty4->last());
        $this->assertEquals(0, iterator_count($empty4));
        $this->assertEmpty(iterator_to_array($empty4));

        $empty5 = $empty1->to($this->event4);

        $this->assertNotSame($empty1, $empty5);
        $this->assertTrue($empty5->empty());
        $this->assertNull($empty5->first());
        $this->assertNull($empty5->last());
        $this->assertEquals(0, iterator_count($empty5));
        $this->assertEmpty(iterator_to_array($empty5));

        $stream = new InMemoryStream($this->event1, $this->event2, $this->event3, $this->event4);

        $this->assertFalse($stream->empty());
        $this->assertSame($this->event1, $stream->first());
        $this->assertSame($this->event4, $stream->last());
        $this->assertEquals(4, iterator_count($stream));
        $this->assertEquals([$this->event1, $this->event2, $this->event3, $this->event4], iterator_to_array($stream));

        $filtered1 = $stream->from($this->event1);

        $this->assertNotSame($stream, $filtered1);
        $this->assertFalse($filtered1->empty());
        $this->assertSame($this->event1, $filtered1->first());
        $this->assertSame($this->event4, $filtered1->last());
        $this->assertEquals(4, iterator_count($filtered1));
        $this->assertEquals([$this->event1, $this->event2, $this->event3, $this->event4], iterator_to_array($filtered1));

        $filtered2 = $stream->from($this->event2);

        $this->assertNotSame($stream, $filtered2);
        $this->assertFalse($filtered2->empty());
        $this->assertSame($this->event2, $filtered2->first());
        $this->assertSame($this->event4, $filtered2->last());
        $this->assertEquals(3, iterator_count($filtered2));
        $this->assertEquals([$this->event2, $this->event3, $this->event4], iterator_to_array($filtered2));

        $filtered3 = $stream->to($this->event4);

        $this->assertNotSame($stream, $filtered3);
        $this->assertFalse($filtered3->empty());
        $this->assertSame($this->event1, $filtered3->first());
        $this->assertSame($this->event4, $filtered3->last());
        $this->assertEquals(4, iterator_count($filtered3));
        $this->assertEquals([$this->event1, $this->event2, $this->event3, $this->event4], iterator_to_array($filtered3));

        $filtered4 = $stream->to($this->event3);

        $this->assertNotSame($stream, $filtered4);
        $this->assertFalse($filtered4->empty());
        $this->assertSame($this->event1, $filtered4->first());
        $this->assertSame($this->event3, $filtered4->last());
        $this->assertEquals(3, iterator_count($filtered4));
        $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($filtered4));

        $filtered5 = $stream->from($this->event2)->to($this->event3);

        $this->assertNotSame($stream, $filtered5);
        $this->assertFalse($filtered5->empty());
        $this->assertSame($this->event2, $filtered5->first());
        $this->assertSame($this->event3, $filtered5->last());
        $this->assertEquals(2, iterator_count($filtered5));
        $this->assertEquals([$this->event2, $this->event3], iterator_to_array($filtered5));

        $filtered6 = $stream->after($this->event1);

        $this->assertNotSame($stream, $filtered6);
        $this->assertFalse($filtered6->empty());
        $this->assertSame($this->event2, $filtered6->first());
        $this->assertSame($this->event4, $filtered6->last());
        $this->assertEquals(3, iterator_count($filtered6));
        $this->assertEquals([$this->event2, $this->event3, $this->event4], iterator_to_array($filtered6));

        $filtered7 = $stream->before($this->event4);

        $this->assertNotSame($stream, $filtered7);
        $this->assertFalse($filtered7->empty());
        $this->assertSame($this->event1, $filtered7->first());
        $this->assertSame($this->event3, $filtered7->last());
        $this->assertEquals(3, iterator_count($filtered7));
        $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($filtered7));

        $empty6 = $stream->before($this->event1);

        $this->assertNotSame($stream, $empty6);
        $this->assertTrue($empty6->empty());
        $this->assertNull($empty6->first());
        $this->assertNull($empty6->last());
        $this->assertEquals(0, iterator_count($empty6));
        $this->assertEmpty(iterator_to_array($empty6));

        $empty7 = $stream->after($this->event4);

        $this->assertNotSame($stream, $empty7);
        $this->assertTrue($empty7->empty());
        $this->assertNull($empty7->first());
        $this->assertNull($empty7->last());
        $this->assertEquals(0, iterator_count($empty7));
        $this->assertEmpty(iterator_to_array($empty7));

        $filtered8 = $stream->limit(2);
        $this->assertFalse($filtered8->empty());
        $this->assertSame($this->event1, $filtered8->first());
        $this->assertSame($this->event2, $filtered8->last());
        $this->assertEquals(2, iterator_count($filtered8));
        $this->assertEquals([$this->event1, $this->event2], iterator_to_array($filtered8));

        $filtered9 = $stream->limit(100);
        $this->assertFalse($filtered9->empty());
        $this->assertSame($this->event1, $filtered9->first());
        $this->assertSame($this->event4, $filtered9->last());
        $this->assertEquals(4, iterator_count($filtered9));
        $this->assertEquals([$this->event1, $this->event2, $this->event3, $this->event4], iterator_to_array($filtered9));
    }
}
