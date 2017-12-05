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
        $event1 = $this->getMockBuilder(Domain\Event::class)->getMockForAbstractClass();
        $event2 = $this->getMockBuilder(Domain\Event::class)->getMockForAbstractClass();
        $event3 = $this->getMockBuilder(Domain\Event::class)->getMockForAbstractClass();
        $event4 = $this->getMockBuilder(Domain\Event::class)->getMockForAbstractClass();
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

    public function onEvent(Domain\Event $event) : void
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
