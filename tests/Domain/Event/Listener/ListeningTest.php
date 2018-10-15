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

use PHPUnit\Framework\TestCase;
use Streak\Domain\Event\Listener\ListeningTest\ListeningStub;
use Streak\Domain\Event\Listener\ListeningTest\SupportedEvent1;
use Streak\Domain\Event\Listener\ListeningTest\SupportedEvent2;
use Streak\Domain\Event\Listener\ListeningTest\SupportedEvent3ThatCausesException;
use Streak\Domain\Event\Listener\ListeningTest\UnsupportedEvent1;
use Streak\Domain\Event\Listener\ListeningTest\UnsupportedEvent2;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Listener\Listening
 */
class ListeningTest extends TestCase
{
    public function testReplayingEmptyStream()
    {
        $listener = new ListeningStub();
        $event1 = new SupportedEvent1();
        $event2 = new UnsupportedEvent1();
        $event3 = new SupportedEvent2();
        $event4 = new UnsupportedEvent2();
        $event5 = new SupportedEvent3ThatCausesException();

        $this->assertEmpty($listener->preEvents());
        $this->assertEmpty($listener->listened());
        $this->assertEmpty($listener->postEvents());
        $this->assertEmpty($listener->exceptions());
        $this->assertFalse($listener->listenerMethodMoreThanOneParameterInMethodActivated());
        $this->assertFalse($listener->listenerMethodWithNullableEventActivated());
        $this->assertFalse($listener->listenerMethodWithParameterThatIsNotSubclassOfEventActivated());

        $this->assertTrue($listener->on($event1));
        $this->assertEquals([$event1], $listener->preEvents());
        $this->assertEquals([$event1], $listener->listened());
        $this->assertEquals([$event1], $listener->postEvents());
        $this->assertEmpty($listener->exceptions());
        $this->assertFalse($listener->listenerMethodMoreThanOneParameterInMethodActivated());
        $this->assertFalse($listener->listenerMethodWithNullableEventActivated());
        $this->assertFalse($listener->listenerMethodWithParameterThatIsNotSubclassOfEventActivated());

        $this->assertFalse($listener->on($event2));
        $this->assertEquals([$event1], $listener->preEvents());
        $this->assertEquals([$event1], $listener->listened());
        $this->assertEquals([$event1], $listener->postEvents());
        $this->assertEmpty($listener->exceptions());
        $this->assertFalse($listener->listenerMethodMoreThanOneParameterInMethodActivated());
        $this->assertFalse($listener->listenerMethodWithNullableEventActivated());
        $this->assertFalse($listener->listenerMethodWithParameterThatIsNotSubclassOfEventActivated());

        $this->assertTrue($listener->on($event3));
        $this->assertEquals([$event1, $event3], $listener->preEvents());
        $this->assertEquals([$event1, $event3], $listener->listened());
        $this->assertEquals([$event1, $event3], $listener->postEvents());
        $this->assertEmpty($listener->exceptions());
        $this->assertFalse($listener->listenerMethodMoreThanOneParameterInMethodActivated());
        $this->assertFalse($listener->listenerMethodWithNullableEventActivated());
        $this->assertFalse($listener->listenerMethodWithParameterThatIsNotSubclassOfEventActivated());

        $this->assertFalse($listener->on($event4));
        $this->assertEquals([$event1, $event3], $listener->preEvents());
        $this->assertEquals([$event1, $event3], $listener->listened());
        $this->assertEquals([$event1, $event3], $listener->postEvents());
        $this->assertEmpty($listener->exceptions());
        $this->assertFalse($listener->listenerMethodMoreThanOneParameterInMethodActivated());
        $this->assertFalse($listener->listenerMethodWithNullableEventActivated());
        $this->assertFalse($listener->listenerMethodWithParameterThatIsNotSubclassOfEventActivated());

        $exception = new \InvalidArgumentException('SupportedEvent3ThatCausesException');
        $this->expectExceptionObject($exception);

        try {
            $listener->on($event5);
        } catch (\Throwable $actual) {
            $this->assertEquals([$event1, $event3, $event5], $listener->preEvents());
            $this->assertEquals([$event1, $event3], $listener->listened());
            $this->assertEquals([$event1, $event3], $listener->postEvents());
            $this->assertEquals([$actual], $listener->exceptions());
            $this->assertFalse($listener->listenerMethodMoreThanOneParameterInMethodActivated());
            $this->assertFalse($listener->listenerMethodWithNullableEventActivated());
            $this->assertFalse($listener->listenerMethodWithParameterThatIsNotSubclassOfEventActivated());

            throw $actual;
        }
    }
}

namespace Streak\Domain\Event\Listener\ListeningTest;

use Streak\Domain\Event;

class ListeningStub
{
    use Event\Listener\Listening;

    private $preEvents = [];
    private $listened = [];
    private $postEvents = [];
    private $exceptions = [];
    private $listenerMethodMoreThanOneParameterInMethodActivated = false;
    private $listenerMethodWithNullableEventActivated = false;
    private $listenerMethodWithParameterThatIsNotSubclassOfEventActivated = false;

    public function onEvent1(SupportedEvent1 $event1)
    {
        $this->listened[] = $event1;
    }

    public function onEvent2(SupportedEvent2 $event2)
    {
        $this->listened[] = $event2;
    }

    public function onEvent1WithAdditionalParameter(SupportedEvent1 $event1, int $secondParameter)
    {
        $this->listenerMethodMoreThanOneParameterInMethodActivated = true;
    }

    public function onNullableEvent1(?SupportedEvent1 $event1)
    {
        $this->listenerMethodWithNullableEventActivated = true;
    }

    public function onObjectWhichIsNotSubclassOfEvent(\stdClass $event)
    {
        $this->listenerMethodWithNullableEventActivated = true;
    }

    public function onSupportedEvent3ThatCausesException(SupportedEvent3ThatCausesException $event3)
    {
        throw new \InvalidArgumentException('SupportedEvent3ThatCausesException');
    }

    public function listened() : array
    {
        return $this->listened;
    }

    public function listenerMethodMoreThanOneParameterInMethodActivated() : bool
    {
        return $this->listenerMethodMoreThanOneParameterInMethodActivated;
    }

    public function listenerMethodWithNullableEventActivated() : bool
    {
        return $this->listenerMethodWithParameterThatIsNotSubclassOfEventActivated;
    }

    public function listenerMethodWithParameterThatIsNotSubclassOfEventActivated() : bool
    {
        return $this->listenerMethodWithParameterThatIsNotSubclassOfEventActivated;
    }

    public function preEvents() : array
    {
        return $this->preEvents;
    }

    public function postEvents() : array
    {
        return $this->postEvents;
    }

    public function exceptions() : array
    {
        return $this->exceptions;
    }

    private function preEvent(Event $event) : void
    {
        $this->preEvents[] = $event;
    }

    private function postEvent(Event $event) : void
    {
        $this->postEvents[] = $event;
    }

    private function onException(\Throwable $exception) : void
    {
        $this->exceptions[] = $exception;
    }
}

class SupportedEvent1 implements Event
{
}
class SupportedEvent2 implements Event
{
}
class SupportedEvent3ThatCausesException implements Event
{
}
class UnsupportedEvent1 implements Event
{
}
class UnsupportedEvent2 implements Event
{
}
