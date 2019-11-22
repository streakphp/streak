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
use Streak\Domain\Event;
use Streak\Domain\Event\Listener\ListeningTest\ListeningStub;
use Streak\Domain\Event\Listener\ListeningTest\SupportedEvent1;
use Streak\Domain\Event\Listener\ListeningTest\SupportedEvent2;
use Streak\Domain\Event\Listener\ListeningTest\SupportedEvent3ThatCausesException;
use Streak\Domain\Event\Listener\ListeningTest\SupportedEvent4;
use Streak\Domain\Event\Listener\ListeningTest\UnsupportedEvent1;
use Streak\Domain\Event\Listener\ListeningTest\UnsupportedEvent2;
use Streak\Domain\Id;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Listener\Listening
 */
class ListeningTest extends TestCase
{
    public function testReplayingEmptyStream()
    {
        $producerId1 = $this->getMockBuilder(Id::class)->getMockForAbstractClass();
        $listener = new ListeningStub();
        $event1 = Event\Envelope::new(new SupportedEvent1(), $producerId1);
        $event2 = Event\Envelope::new(new UnsupportedEvent1(), $producerId1);
        $event3 = Event\Envelope::new(new SupportedEvent2(), $producerId1);
        $event4 = Event\Envelope::new(new UnsupportedEvent2(), $producerId1);
        $event5 = Event\Envelope::new(new SupportedEvent3ThatCausesException(), $producerId1);

        $this->assertEmpty($listener->preEvents());
        $this->assertEmpty($listener->listened());
        $this->assertEmpty($listener->postEvents());
        $this->assertEmpty($listener->exceptions());
        $this->assertFalse($listener->listenerMethodMoreThanOneParameterInMethodActivated());
        $this->assertFalse($listener->listenerMethodWithNullableEventActivated());
        $this->assertFalse($listener->listenerMethodWithParameterThatIsNotSubclassOfEventActivated());

        $this->assertTrue($listener->on($event1));
        $this->assertEquals([$event1->message()], $listener->preEvents());
        $this->assertEquals([$event1->message()], $listener->listened());
        $this->assertEquals([$event1->message()], $listener->postEvents());
        $this->assertEmpty($listener->exceptions());
        $this->assertFalse($listener->listenerMethodMoreThanOneParameterInMethodActivated());
        $this->assertFalse($listener->listenerMethodWithNullableEventActivated());
        $this->assertFalse($listener->listenerMethodWithParameterThatIsNotSubclassOfEventActivated());

        $this->assertFalse($listener->on($event2));
        $this->assertEquals([$event1->message()], $listener->preEvents());
        $this->assertEquals([$event1->message()], $listener->listened());
        $this->assertEquals([$event1->message()], $listener->postEvents());
        $this->assertEmpty($listener->exceptions());
        $this->assertFalse($listener->listenerMethodMoreThanOneParameterInMethodActivated());
        $this->assertFalse($listener->listenerMethodWithNullableEventActivated());
        $this->assertFalse($listener->listenerMethodWithParameterThatIsNotSubclassOfEventActivated());

        $this->assertTrue($listener->on($event3));
        $this->assertEquals([$event1->message(), $event3->message()], $listener->preEvents());
        $this->assertEquals([$event1->message(), $event3->message()], $listener->listened());
        $this->assertEquals([$event1->message(), $event3->message()], $listener->postEvents());
        $this->assertEmpty($listener->exceptions());
        $this->assertFalse($listener->listenerMethodMoreThanOneParameterInMethodActivated());
        $this->assertFalse($listener->listenerMethodWithNullableEventActivated());
        $this->assertFalse($listener->listenerMethodWithParameterThatIsNotSubclassOfEventActivated());

        $this->assertFalse($listener->on($event4));
        $this->assertEquals([$event1->message(), $event3->message()], $listener->preEvents());
        $this->assertEquals([$event1->message(), $event3->message()], $listener->listened());
        $this->assertEquals([$event1->message(), $event3->message()], $listener->postEvents());
        $this->assertEmpty($listener->exceptions());
        $this->assertFalse($listener->listenerMethodMoreThanOneParameterInMethodActivated());
        $this->assertFalse($listener->listenerMethodWithNullableEventActivated());
        $this->assertFalse($listener->listenerMethodWithParameterThatIsNotSubclassOfEventActivated());

        $exception = new \InvalidArgumentException('SupportedEvent3ThatCausesException');
        $this->expectExceptionObject($exception);

        try {
            $listener->on($event5);
        } catch (\Throwable $actual) {
            $this->assertEquals([$event1->message(), $event3->message(), $event5->message()], $listener->preEvents());
            $this->assertEquals([$event1->message(), $event3->message()], $listener->listened());
            $this->assertEquals([$event1->message(), $event3->message()], $listener->postEvents());
            $this->assertEquals([$actual], $listener->exceptions());
            $this->assertFalse($listener->listenerMethodMoreThanOneParameterInMethodActivated());
            $this->assertFalse($listener->listenerMethodWithNullableEventActivated());
            $this->assertFalse($listener->listenerMethodWithParameterThatIsNotSubclassOfEventActivated());

            throw $actual;
        }
    }

    /**
     * @dataProvider listeningMethodReturnsNonBooleanValueDataProvider
     */
    public function testListeningMethodReturnsNonBooleanValue($returnedValue, \UnexpectedValueException $expectedException)
    {
        $this->expectExceptionObject($expectedException);

        $listener = new ListeningStub();
        $listener->on(Event\Envelope::new(new SupportedEvent4($returnedValue), $this->getMockBuilder(Id::class)->getMockForAbstractClass()));
    }

    public function testListeningMethodReturnsTrue()
    {
        $listener = new ListeningStub();
        $isEventListenedTo = $listener->on(Event\Envelope::new(new SupportedEvent4(true), $this->getMockBuilder(Id::class)->getMockForAbstractClass()));

        $this->assertTrue($isEventListenedTo);
    }

    public function testListeningMethodReturnsFalse()
    {
        $listener = new ListeningStub();
        $isEventListenedTo = $listener->on(Event\Envelope::new(new SupportedEvent4(false), $this->getMockBuilder(Id::class)->getMockForAbstractClass()));

        $this->assertFalse($isEventListenedTo);
    }

    public function listeningMethodReturnsNonBooleanValueDataProvider()
    {
        return [
            // integers
            [-128, new \UnexpectedValueException('Value returned by ListeningStub::onSupportedEvent4($event) expected to be null or boolean, but integer was given.')],
            [-1, new \UnexpectedValueException('Value returned by ListeningStub::onSupportedEvent4($event) expected to be null or boolean, but integer was given.')],
            [0, new \UnexpectedValueException('Value returned by ListeningStub::onSupportedEvent4($event) expected to be null or boolean, but integer was given.')],
            [128, new \UnexpectedValueException('Value returned by ListeningStub::onSupportedEvent4($event) expected to be null or boolean, but integer was given.')],
            // floats
            [-128.555, new \UnexpectedValueException('Value returned by ListeningStub::onSupportedEvent4($event) expected to be null or boolean, but double was given.')],
            [-1.555, new \UnexpectedValueException('Value returned by ListeningStub::onSupportedEvent4($event) expected to be null or boolean, but double was given.')],
            [0.0, new \UnexpectedValueException('Value returned by ListeningStub::onSupportedEvent4($event) expected to be null or boolean, but double was given.')],
            [128.555, new \UnexpectedValueException('Value returned by ListeningStub::onSupportedEvent4($event) expected to be null or boolean, but double was given.')],
            // arrays
            [[], new \UnexpectedValueException('Value returned by ListeningStub::onSupportedEvent4($event) expected to be null or boolean, but array was given.')],
            [[0, 0.0, null, []], new \UnexpectedValueException('Value returned by ListeningStub::onSupportedEvent4($event) expected to be null or boolean, but array was given.')],
            [[1, 1.55, new \stdClass()], new \UnexpectedValueException('Value returned by ListeningStub::onSupportedEvent4($event) expected to be null or boolean, but array was given.')],
            // objects
            [new \stdClass(), new \UnexpectedValueException('Value returned by ListeningStub::onSupportedEvent4($event) expected to be null or boolean, but object was given.')],
            [new SupportedEvent1(), new \UnexpectedValueException('Value returned by ListeningStub::onSupportedEvent4($event) expected to be null or boolean, but object was given.')],
            // strings
            ['', new \UnexpectedValueException('Value returned by ListeningStub::onSupportedEvent4($event) expected to be null or boolean, but string was given.')],
            ['qwerty', new \UnexpectedValueException('Value returned by ListeningStub::onSupportedEvent4($event) expected to be null or boolean, but string was given.')],
        ];
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

    public function onSupportedEvent1ButReturnValueIsNotVoidOrBoolean(SupportedEvent1 $event1) : string
    {
        return 'string';
    }

    public function onObjectWhichIsNotSubclassOfEvent(\stdClass $event)
    {
        $this->listenerMethodWithNullableEventActivated = true;
    }

    public function onSupportedEvent3ThatCausesException(SupportedEvent3ThatCausesException $event3)
    {
        throw new \InvalidArgumentException('SupportedEvent3ThatCausesException');
    }

    public function onSupportedEvent4(SupportedEvent4 $event4)
    {
        return $event4->value();
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
class SupportedEvent4 implements Event
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function value()
    {
        return $this->value;
    }
}
class UnsupportedEvent1 implements Event
{
}
class UnsupportedEvent2 implements Event
{
}
