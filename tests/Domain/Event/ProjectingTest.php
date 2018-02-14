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

        $projector->on($event1);
        $projector->on($event2);
        $projector->on($event3);
        $projector->on($event4);

        $this->assertSame($events, $projector->eventsThatPreEventHookFiredFor());
        $this->assertSame($events, $projector->eventsThatPostEventHookFiredFor());
        $this->assertEmpty($projector->exceptionsThatOnExceptionHookFiredFor());
        $this->assertEquals($events, $projector->consumed());
    }

    public function testErrors()
    {
        $exception = new \RuntimeException('Consuming with error.');
        $this->expectExceptionObject($exception);

        $event1 = new ProjectingTest\Event1Stub();
        $event2 = new ProjectingTest\Event2Stub();
        $event3 = new ProjectingTest\Event3Stub();
        $event4 = new ProjectingTest\Event4Stub();
        $event5 = new ProjectingTest\Event5Stub();

        $projector = new ProjectorStub();

        try {
            $projector->on($event1);
            $projector->on($event2);
            $projector->on($event3);
            $projector->on($event4);
            $projector->on($event5);
        } catch (\Exception $thrown) {
            $this->assertSame([$event1, $event2, $event3, $event4, $event5], $projector->eventsThatPreEventHookFiredFor());
            $this->assertSame([$event1, $event2, $event3, $event4], $projector->eventsThatPostEventHookFiredFor());
            $this->assertSame([$thrown], $projector->exceptionsThatOnExceptionHookFiredFor());

            throw $thrown;
        }
    }
}

namespace Streak\Domain\Event\ProjectingTest;

use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Sensor;

class ProjectorStub
{
    use Event\Projecting;

    private $consumed = [];
    private $eventsThatPreEventHookFiredFor = [];
    private $eventsThatPostEventHookFiredFor = [];
    private $exceptionsThatOnExceptionHookFiredFor = [];

    public function reset() : void
    {
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

    public function onEvent5Stub(Event5Stub $event) : void
    {
        $this->consumed[] = $event;

        throw new \RuntimeException('Consuming with error.');
    }

    /**
     * @return Domain\Event[]
     */
    public function consumed() : array
    {
        return $this->consumed;
    }

    /**
     * @return Event[]
     */
    public function eventsThatPreEventHookFiredFor() : array
    {
        return $this->eventsThatPreEventHookFiredFor;
    }

    /**
     * @return Domain\Event[]
     */
    public function eventsThatPostEventHookFiredFor() : array
    {
        return $this->eventsThatPostEventHookFiredFor;
    }

    /**
     * @return \Exception[]
     */
    public function exceptionsThatOnExceptionHookFiredFor() : array
    {
        return $this->exceptionsThatOnExceptionHookFiredFor;
    }

    protected function preEvent(Event $event) : void
    {
        $this->eventsThatPreEventHookFiredFor[] = $event;
    }

    protected function postEvent(Event $event) : void
    {
        $this->eventsThatPostEventHookFiredFor[] = $event;
    }

    protected function onException(\Exception $exception) : void
    {
        $this->exceptionsThatOnExceptionHookFiredFor[] = $exception;
    }
}

class Event1Stub implements Domain\Event
{
    public function aggregateRootId() : Domain\AggregateRoot\Id
    {
    }

    public function producerId() : Domain\Id
    {
    }
}

class Event2Stub implements Domain\Event
{
    public function aggregateRootId() : Domain\AggregateRoot\Id
    {
    }

    public function producerId() : Domain\Id
    {
    }
}

class Event3Stub implements Domain\Event
{
    public function aggregateRootId() : Domain\AggregateRoot\Id
    {
    }

    public function producerId() : Domain\Id
    {
    }
}

class Event4Stub implements Domain\Event
{
    public function aggregateRootId() : Domain\AggregateRoot\Id
    {
    }

    public function producerId() : Domain\Id
    {
    }
}

class Event5Stub implements Domain\Event
{
    public function aggregateRootId() : Domain\AggregateRoot\Id
    {
    }

    public function producerId() : Domain\Id
    {
    }
}

class ActorEvent1Stub implements Domain\Event
{
    public function actorId() : Sensor\Id
    {
    }

    public function producerId() : Domain\Id
    {
    }
}
