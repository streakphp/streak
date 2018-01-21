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

namespace Streak\Domain\Event;

use PHPUnit\Framework\TestCase;
use Streak\Domain;
use Streak\Domain\Event\ListeningTest\Event1;
use Streak\Domain\Event\ListeningTest\Event2;
use Streak\Domain\Event\ListeningTest\Event3;
use Streak\Domain\Event\ListeningTest\Event4;
use Streak\Domain\Event\ListeningTest\ListenerStub;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Listening
 */
class ListeningTest extends TestCase
{
    public function testListening()
    {
        $listener = new ListenerStub();

        $this->assertFalse($listener->isEvent1Listened());
        $this->assertFalse($listener->isEvent2Listened());
        $this->assertFalse($listener->isEvent3Listened());
        $this->assertFalse($listener->isNonEventListened());

        $this->assertTrue($listener->on(new Event1()));

        $this->assertTrue($listener->isEvent1Listened());
        $this->assertFalse($listener->isEvent2Listened());
        $this->assertFalse($listener->isEvent3Listened());
        $this->assertFalse($listener->isNonEventListened());

        $this->assertFalse($listener->on(new Event2()));

        $this->assertTrue($listener->isEvent1Listened());
        $this->assertFalse($listener->isEvent2Listened());
        $this->assertFalse($listener->isEvent3Listened());
        $this->assertFalse($listener->isNonEventListened());

        $this->assertFalse($listener->on(new Event3()));

        $this->assertTrue($listener->isEvent1Listened());
        $this->assertFalse($listener->isEvent2Listened());
        $this->assertFalse($listener->isEvent3Listened());
        $this->assertFalse($listener->isNonEventListened());

        $this->assertFalse($listener->on(new Event4()));

        $this->assertTrue($listener->isEvent1Listened());
        $this->assertFalse($listener->isEvent2Listened());
        $this->assertFalse($listener->isEvent3Listened());
        $this->assertFalse($listener->isNonEventListened());
    }
}

namespace Streak\Domain\Event\ListeningTest;

use Streak\Domain;
use Streak\Domain\Event;

class ListenerStub
{
    use Event\Listening;

    private $event1Listened = false;
    private $event2Listened = false;
    private $event3Listened = false;
    private $nonEventListened = false;

    public function onEvent1(Event1 $event1)
    {
        $this->event1Listened = true;
    }

    public function onEvent2WithOptionalEvent(Event2 $event2 = null)
    {
    }

    public function onEvent2WithAdditionalUnnecessaryParameter(Event2 $event2, $unnecessary)
    {
    }

    public function onNonEvent(\stdClass $parameter)
    {
        $this->nonEventListened = true;
    }

    public function isEvent1Listened() : bool
    {
        return $this->event1Listened;
    }

    public function isEvent2Listened() : bool
    {
        return $this->event2Listened;
    }

    public function isEvent3Listened() : bool
    {
        return $this->event3Listened;
    }

    public function isNonEventListened() : bool
    {
        return $this->nonEventListened;
    }

    protected function onEvent3ButProtected(Event3 $event3)
    {
        $this->event3Listened = true;
    }
}

abstract class EventStub implements Domain\Event
{
}
class Event1 extends EventStub
{
}
class Event2 extends EventStub
{
}
class Event3 extends EventStub
{
}
class Event4 extends EventStub
{
}
class MessageStub implements Domain\Event
{
}
