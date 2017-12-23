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
use Streak\Domain\Event\ProjectingTest\ProjectorStub;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Projecting
 */
class ProjectingTest extends TestCase
{
    public function testProjecting()
    {
        $event1 = new ProjectingTest\Event1Stub();
        $event2 = new ProjectingTest\Event2Stub();
        $event3 = new ProjectingTest\Event3Stub();
        $event4 = new ProjectingTest\Event4Stub();
        $events = [$event1, $event2, $event3, $event4];

        $projector = new ProjectorStub();

        $this->assertEmpty($projector->consumed());
        $this->assertNull($projector->lastReplayed());
        $this->assertFalse($projector->replayed());

        $projector->replay(...$events);

        $this->assertEquals($events, $projector->consumed());
        $this->assertEquals($event4, $projector->lastReplayed());
        $this->assertTrue($projector->replayed());
    }
}

namespace Streak\Domain\Event\ProjectingTest;

use Streak\Domain;
use Streak\Domain\Event;

class ProjectorStub
{
    use Event\Projecting;

    private $replayed = false;
    private $consumed = [];

    public function onReplay() : void
    {
        $this->replayed = true;
    }

    public function onEvent1Stub(Event1Stub $event) : void
    {
        $this->consumed[] = $event;
    }

    public function onEvent2Stub(Event2Stub $event) : void
    {
        $this->consumed[] = $event;
    }

    public function onEvent3Stub(Event3Stub $event) : void
    {
        $this->consumed[] = $event;
    }

    public function onEvent4Stub(Event4Stub $event) : void
    {
        $this->consumed[] = $event;
    }

    /**
     * @return Domain\Event[]
     */
    public function consumed() : array
    {
        return $this->consumed;
    }

    public function replayed() : bool
    {
        return $this->replayed;
    }
}

class Event1Stub implements Domain\Event
{
    public function producerId() : Domain\Id
    {
    }
}

class Event2Stub implements Domain\Event
{
    public function producerId() : Domain\Id
    {
    }
}

class Event3Stub implements Domain\Event
{
    public function producerId() : Domain\Id
    {
    }
}

class Event4Stub implements Domain\Event
{
    public function producerId() : Domain\Id
    {
    }
}
