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

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Sourced\Subscription\Stream
 */
class StreamTest extends TestCase
{
    /**
     * @var Event\Stream|MockObject
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
        $this->stream = $this->getMockBuilder(Event\Stream::class)->getMockForAbstractClass();

        $this->event1 = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $this->event2 = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $this->event3 = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $this->event4 = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
    }

    public function testStream()
    {
        $this->isIteratorFor($this->stream, [$this->event1, new SubscriptionListenedToEvent($this->event2), $this->event3, new SubscriptionListenedToEvent($this->event4)]);

        $stream = new Stream($this->stream);

        $this->assertEquals([$this->event2, $this->event4], iterator_to_array($stream));
    }

    public function testEmpty()
    {
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

    private function isIteratorFor(MockObject $iterator, array $items)
    {
        $internal = new \ArrayIterator($items);

        $iterator
            ->expects($this->any())
            ->method('rewind')
            ->willReturnCallback(function () use ($internal) {
                $internal->rewind();
            })
        ;

        $iterator
            ->expects($this->any())
            ->method('current')
            ->willReturnCallback(function () use ($internal) {
                return $internal->current();
            })
        ;

        $iterator
            ->expects($this->any())
            ->method('key')
            ->willReturnCallback(function () use ($internal) {
                return $internal->key();
            })
        ;

        $iterator
            ->expects($this->any())
            ->method('next')
            ->willReturnCallback(function () use ($internal) {
                $internal->next();
            })
        ;

        $iterator
            ->expects($this->any())
            ->method('valid')
            ->willReturnCallback(function () use ($internal) {
                return $internal->valid();
            })
        ;

        return $iterator;
    }
}
