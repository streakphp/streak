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

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Listener\Filtering
 */
class FilteringTest extends TestCase
{
    /**
     * @var Event\Stream|MockObject
     */
    private $stream;

    protected function setUp() : void
    {
        $this->stream = $this->getMockBuilder(Event\Stream::class)->getMockForAbstractClass();
    }

    public function testSuccessfulFiltering()
    {
        $filterer = new FilteringTest\ListeningStub1();

        $this->stream
            ->expects($this->once())
            ->method('only')
            ->with(
                FilteringTest\SupportedEvent1::class,
                FilteringTest\SupportedEvent2::class
            )
            ->willReturnSelf()
        ;

        $stream = $filterer->filter($this->stream);

        $this->assertSame($this->stream, $stream);
    }

    public function testFilteringWithEventThaIsNotFinal()
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Event class "Streak\Domain\Event\Listener\FilteringTest\NotSupportedEvent1" must be final in order to be used for stream filtering.'));

        $filterer = new FilteringTest\ListeningStub2();
        $filterer->filter($this->stream);
    }
}

namespace Streak\Domain\Event\Listener\FilteringTest;

use Streak\Domain\Event;

class ListeningStub1
{
    use Event\Listener\Filtering;

    public function notStartingWithOn(SupportedEvent1 $event) : void
    {
    }

    public function onSupportedEvent1ButWithSecondParameter(SupportedEvent1 $event, SupportedEvent1 $second) : void
    {
    }

    public function onSupportedOptionalEvent1(?SupportedEvent1 $event) : void
    {
    }

    public function onStdClass(\stdClass $event) : void
    {
    }

    public function onSupportedEvent1(SupportedEvent1 $event) : void
    {
    }

    public function onSupportedEvent2(SupportedEvent2 $event) : void
    {
    }

    private function onSupportedEvent1ButPrivate(SupportedEvent1 $event) : void
    {
    }
}

class ListeningStub2
{
    use Event\Listener\Filtering;

    public function onNotSupportedEvent1(NotSupportedEvent1 $event) : void
    {
    }
}

final class SupportedEvent1 implements Event
{
}
final class SupportedEvent2 implements Event
{
}
class NotSupportedEvent1 implements Event
{
}
