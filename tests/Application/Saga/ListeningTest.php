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

namespace Streak\Application\Saga;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Application\CommandBus;
use Streak\Application\Saga\ListeningTest\Command1;
use Streak\Application\Saga\ListeningTest\Command2;
use Streak\Application\Saga\ListeningTest\Event1;
use Streak\Application\Saga\ListeningTest\Event2;
use Streak\Application\Saga\ListeningTest\ListeningStub;
use Streak\Application\Saga\ListeningTest\UnsupportedEvent1;
use Streak\Application\Saga\ListeningTest\UnsupportedEvent2;
use Streak\Infrastructure\Event\InMemoryStream;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Application\Saga\Listening
 */
class ListeningTest extends TestCase
{
    /**
     * @var CommandBus|MockObject
     */
    private $bus;

    protected function setUp()
    {
        $this->bus = $this->getMockBuilder(CommandBus::class)->getMockForAbstractClass();
    }

    public function testReplayingEmptyStream()
    {
        $listener = new ListeningStub($this->bus);
        $event1 = new Event1();
        $event2 = new Event2();

        $listener->replay(new InMemoryStream());
        $this->assertEmpty($listener->listened());

        $this->bus
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(Command1::class)],
                [$this->isInstanceOf(Command2::class)]
            )
        ;

        $return = $listener->on($event1);
        $this->assertTrue($return);
        $this->assertEquals([$event1], $listener->listened());

        $return = $listener->on($event2);
        $this->assertTrue($return);
        $this->assertEquals([$event1, $event2], $listener->listened());
    }

    public function testReplayingStream()
    {
        $listener = new ListeningStub($this->bus);
        $event1 = new Event1();
        $event2 = new Event2();

        $listener->replay(new InMemoryStream($event1));

        $this->assertEquals([$event1], $listener->listened());

        $this->bus
            ->expects($this->exactly(1))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(Command2::class)]
            )
        ;

        $listener->on($event2);

        $this->assertEquals([$event1, $event2], $listener->listened());
    }

    public function testListeningToUnsupportedEvents()
    {
        $listener = new ListeningStub($this->bus);
        $this->assertEmpty($listener->listened());

        $event1 = new UnsupportedEvent1();
        $event2 = new UnsupportedEvent2();

        $listener->replay(new InMemoryStream($event1));
        $this->assertEmpty($listener->listened());

        $return = $listener->on($event2);
        $this->assertFalse($return);
        $this->assertEmpty($listener->listened());
    }
}

namespace Streak\Application\Saga\ListeningTest;

use Streak\Application\Command;
use Streak\Application\Saga;
use Streak\Domain\Event;

class ListeningStub
{
    use Saga\Listening;

    private $listened = [];

    public function onEvent1(Event1 $event1)
    {
        $this->listened[] = $event1;

        $this->bus->dispatch(new Command1());
    }

    public function onEvent2(Event2 $event2)
    {
        $this->listened[] = $event2;

        $this->bus->dispatch(new Command2());
    }

    public function listened() : array
    {
        return $this->listened;
    }
}

class Event1 implements Event
{
}
class Event2 implements Event
{
}
class UnsupportedEvent1 implements Event
{
}
class UnsupportedEvent2 implements Event
{
}

class Command1 implements Command
{
}
class Command2 implements Command
{
}
