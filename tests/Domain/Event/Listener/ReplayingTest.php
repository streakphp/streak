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
    private IterableStream $stream;

    protected function setUp(): void
    {
        $this->stream = $this->getMockBuilder(IterableStream::class)->getMock();
    }

    public function testReplaying(): void
    {
        $event1 = new SupportedEvent1();
        $event1 = Event\Envelope::new($event1, UUID::random());
        $event2 = new SupportedEvent2();
        $event2 = Event\Envelope::new($event2, UUID::random());

        $this->stream
            ->expects(self::atLeastOnce())
            ->method('empty')
            ->with()
            ->willReturn(false)
        ;
        $this->isIteratorFor($this->stream, [$event1, $event2]);

        $stub = new ReplayingStub();

        self::assertEmpty($stub->listened());
        self::assertEquals(0, $stub->enableSideEffectsCalled());
        self::assertEquals(0, $stub->disableSideEffectsCalled());

        $stub->replay($this->stream);

        self::assertEquals([$event1, $event2], $stub->listened());
        self::assertEquals(1, $stub->enableSideEffectsCalled());
        self::assertEquals(1, $stub->disableSideEffectsCalled());

        $stub->replay($this->stream);

        self::assertEquals([$event1, $event2, $event1, $event2], $stub->listened());
        self::assertEquals(2, $stub->enableSideEffectsCalled());
        self::assertEquals(2, $stub->disableSideEffectsCalled());
    }

    public function testReplayingEmptyStream(): void
    {
        $this->stream
            ->expects(self::once())
            ->method('empty')
            ->with()
            ->willReturn(true)
        ;
        $this->isIteratorFor($this->stream, []);

        $stub = new ReplayingStub();

        self::assertEmpty($stub->listened());
        self::assertEquals(0, $stub->enableSideEffectsCalled());
        self::assertEquals(0, $stub->disableSideEffectsCalled());

        $stub->replay($this->stream);

        self::assertEmpty($stub->listened());
        self::assertEquals(0, $stub->enableSideEffectsCalled());
        self::assertEquals(0, $stub->disableSideEffectsCalled());
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

    public function onEvent1(SupportedEvent1 $event1): void
    {
        $this->listened[] = $event1;
    }

    public function onEvent2(SupportedEvent2 $event2): void
    {
        $this->listened[] = $event2;
    }

    public function enableSideEffectsCalled(): int
    {
        return $this->enableSideEffectsCalled;
    }

    public function disableSideEffectsCalled(): int
    {
        return $this->disableSideEffectsCalled;
    }

    public function listened()
    {
        return $this->listened;
    }

    protected function on(Event\Envelope $event): bool
    {
        $this->listened[] = $event;

        return true;
    }

    protected function disableSideEffects(): void
    {
        ++$this->disableSideEffectsCalled;
    }

    protected function enableSideEffects(): void
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
