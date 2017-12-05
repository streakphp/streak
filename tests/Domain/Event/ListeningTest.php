<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain\Event;

use PHPUnit\Framework\TestCase;
use Streak\Domain;
use Streak\Domain\Event\ListeningTest\ListenerStub;
use Streak\Domain\Event\ListeningTest\Event1;
use Streak\Domain\Event\ListeningTest\Event2;
use Streak\Domain\Event\ListeningTest\Event3;
use Streak\Domain\Event\ListeningTest\Event4;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Listening
 */
class ListeningTest extends TestCase
{
    private $id;

    public function setUp()
    {
        $this->id = $this->getMockBuilder(Domain\Id::class)->getMockForAbstractClass();
    }

    public function testListening()
    {
        $listener = new ListenerStub();

        $this->assertFalse($listener->isEvent1Listened());
        $this->assertFalse($listener->isEvent2Listened());
        $this->assertFalse($listener->isEvent3Listened());
        $this->assertFalse($listener->isEvent4Listened());

        $listener->onEvent(new Event1($this->id));

        $this->assertTrue($listener->isEvent1Listened());
        $this->assertFalse($listener->isEvent2Listened());
        $this->assertFalse($listener->isEvent3Listened());
        $this->assertFalse($listener->isEvent4Listened());

        $listener->onEvent(new Event2($this->id));

        $this->assertTrue($listener->isEvent1Listened());
        $this->assertFalse($listener->isEvent2Listened());
        $this->assertFalse($listener->isEvent3Listened());
        $this->assertFalse($listener->isEvent4Listened());

        $listener->onEvent(new Event3($this->id));

        $this->assertTrue($listener->isEvent1Listened());
        $this->assertFalse($listener->isEvent2Listened());
        $this->assertFalse($listener->isEvent3Listened());
        $this->assertFalse($listener->isEvent4Listened());

        $listener->onEvent(new Event4($this->id));

        $this->assertTrue($listener->isEvent1Listened());
        $this->assertFalse($listener->isEvent2Listened());
        $this->assertFalse($listener->isEvent3Listened());
        $this->assertFalse($listener->isEvent4Listened());
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
    private $event4Listened = false;

    public function onEvent1(Event1 $event1)
    {
        $this->event1Listened = true;
    }

    public function onEvent1WithOptionalEvent(Event2 $event2 = null)
    {
    }

    public function onEvent1WithAdditionalUnnecessaryParameter(Event2 $event2, $unnecessary)
    {
    }

    public function onNonEvent(\stdClass $parameter)
    {
    }

    protected function onEvent3(Event3 $event3)
    {
        $this->event3Listened = true;
    }

    protected function onEvent4(Event4 $event4)
    {
        $this->event4Listened = true;
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

    public function isEvent4Listened() : bool
    {
        return $this->event4Listened;
    }
}

abstract class EventStub implements Domain\Event
{
    private $id;

    public function __construct(Domain\Id$id)
    {
        $this->id = $id;
    }

    public function producerId() : Domain\Id
    {
        return $this->id;
    }
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
