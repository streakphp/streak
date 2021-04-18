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

namespace Streak\Infrastructure\EventBus;

use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\Id\UUID;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\EventBus\InMemoryEventBus
 */
class InMemoryEventBusTest extends TestCase
{
    private Event\Listener $listener1;
    private Event\Listener $listener2;
    private Event\Listener $listener3;

    private Event\Envelope $event1;
    private Event\Envelope $event2;
    private Event\Envelope $event3;

    protected function setUp(): void
    {
        $this->listener1 = $this->getMockBuilder(Event\Listener::class)->setMockClassName('listener1')->getMockForAbstractClass();
        $this->listener2 = $this->getMockBuilder(Event\Listener::class)->setMockClassName('listener2')->getMockForAbstractClass();
        $this->listener3 = $this->getMockBuilder(Event\Listener::class)->setMockClassName('listener3')->getMockForAbstractClass();

        $this->event1 = Event\Envelope::new($this->getMockBuilder(Event::class)->setMockClassName('event1')->getMockForAbstractClass(), UUID::random());
        $this->event2 = Event\Envelope::new($this->getMockBuilder(Event::class)->setMockClassName('event2')->getMockForAbstractClass(), UUID::random());
        $this->event3 = Event\Envelope::new($this->getMockBuilder(Event::class)->setMockClassName('event3')->getMockForAbstractClass(), UUID::random());
    }

    public function testBus(): void
    {
        $bus = new InMemoryEventBus();

        $bus->add($this->listener1);
        $bus->add($this->listener1); // should be ignored

        $this->listener1
            ->expects(self::exactly(2))
            ->method('on')
            ->withConsecutive(
                [$this->event1],
                [$this->event2]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                true
            )
        ;

        $bus->publish($this->event1);

        $this->listener3
            ->expects(self::exactly(2))
            ->method('on')
            ->withConsecutive(
                [$this->event2],
                [$this->event3]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                true
            )
        ;

        $this->listener2
            ->expects(self::at(0))
            ->method('on')
            ->with($this->event2)
            ->willReturnCallback(function () use ($bus) {
                $bus->remove($this->listener1);
                $bus->add($this->listener3);
                $bus->publish($this->event3);

                return true;
            })
        ;

        $this->listener2
            ->expects(self::at(1))
            ->method('on')
            ->with($this->event3)
            ->willReturn(true)
        ;

        $bus->add($this->listener2);

        $bus->publish($this->event2); // listener2 published event3, added listener3 and removed listener1

        $events = [];
        $bus->publish(...$events);
    }
}
