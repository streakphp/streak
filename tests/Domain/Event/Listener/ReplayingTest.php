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

namespace Streak\Domain\Event\Listener;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener\ReplayingTest\IterableStream;
use Streak\Domain\Event\Listener\ReplayingTest\ReplayingStub;
use Streak\Domain\Event\Listener\ReplayingTest\SupportedEvent1;
use Streak\Domain\Event\Listener\ReplayingTest\SupportedEvent2;
use Streak\Domain\Id\UUID;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Listener\Replaying
 */
class ReplayingTest extends TestCase
{
    /**
     * @var Event\Stream|MockObject
     */
    private $stream;

    public function setUp() : void
    {
        $this->stream = $this->getMockBuilder(IterableStream::class)->getMock();
    }

    public function testReplaying()
    {
        $event1 = new SupportedEvent1();
        $event1 = Event\Envelope::new($event1, UUID::random());
        $event2 = new SupportedEvent2();
        $event2 = Event\Envelope::new($event2, UUID::random());

        $this->stream
            ->expects($this->atLeastOnce())
            ->method('empty')
            ->with()
            ->willReturn(false)
        ;
        $this->isIteratorFor($this->stream, [$event1, $event2]);

        $stub = new ReplayingStub();

        $this->assertEmpty($stub->listened());
        $this->assertEquals(0, $stub->enableSideEffectsCalled());
        $this->assertEquals(0, $stub->disableSideEffectsCalled());

        $stub->replay($this->stream);

        $this->assertEquals([$event1, $event2], $stub->listened());
        $this->assertEquals(1, $stub->enableSideEffectsCalled());
        $this->assertEquals(1, $stub->disableSideEffectsCalled());

        $stub->replay($this->stream);

        $this->assertEquals([$event1, $event2, $event1, $event2], $stub->listened());
        $this->assertEquals(2, $stub->enableSideEffectsCalled());
        $this->assertEquals(2, $stub->disableSideEffectsCalled());
    }

    public function testReplayingEmptyStream()
    {
        $this->stream
            ->expects($this->once())
            ->method('empty')
            ->with()
            ->willReturn(true)
        ;
        $this->isIteratorFor($this->stream, []);

        $stub = new ReplayingStub();

        $this->assertEmpty($stub->listened());
        $this->assertEquals(0, $stub->enableSideEffectsCalled());
        $this->assertEquals(0, $stub->disableSideEffectsCalled());

        $stub->replay($this->stream);

        $this->assertEmpty($stub->listened());
        $this->assertEquals(0, $stub->enableSideEffectsCalled());
        $this->assertEquals(0, $stub->disableSideEffectsCalled());
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

namespace Streak\Domain\Event\Listener\ReplayingTest;

use Streak\Domain\Event;

class ReplayingStub
{
    use Event\Listener\Replaying;

    private int $enableSideEffectsCalled = 0;
    private int $disableSideEffectsCalled = 0;
    private ?array $listened = null;

    public function onEvent1(SupportedEvent1 $event1)
    {
        $this->listened[] = $event1;
    }

    public function onEvent2(SupportedEvent2 $event2)
    {
        $this->listened[] = $event2;
    }

    public function enableSideEffectsCalled() : int
    {
        return $this->enableSideEffectsCalled;
    }

    public function disableSideEffectsCalled() : int
    {
        return $this->disableSideEffectsCalled;
    }

    public function listened()
    {
        return $this->listened;
    }

    protected function on(Event\Envelope $event) : bool
    {
        $this->listened[] = $event;

        return true;
    }

    protected function disableSideEffects() : void
    {
        ++$this->disableSideEffectsCalled;
    }

    protected function enableSideEffects() : void
    {
        ++$this->enableSideEffectsCalled;
    }
}

class SupportedEvent1 implements Event
{
}
class SupportedEvent2 implements Event
{
}

abstract class IterableStream implements Event\Stream, \IteratorAggregate
{
}
