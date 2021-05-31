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

namespace Streak\Infrastructure\Domain\EventBus;

use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\Id\UUID;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\EventBus\InMemoryEventBus
 */
class NullEventBusTest extends TestCase
{
    private Event\Listener $listener1;
    private Event\Listener $listener2;
    private Event\Listener $listener3;

    private Event\Envelope $event1;
    private Event\Envelope  $event2;
    private Event\Envelope  $event3;

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
        $bus = new NullEventBus();

        $bus->add($this->listener1);
        $bus->add($this->listener1); // should be ignored

        $this->listener1
            ->expects(self::never())
            ->method('on')
        ;

        $this->listener2
            ->expects(self::never())
            ->method('on')
        ;

        $this->listener3
            ->expects(self::never())
            ->method('on')
        ;

        $bus->publish($this->event1);

        $bus->add($this->listener2);

        $bus->publish($this->event2);

        $bus->remove($this->listener1);

        $bus->publish($this->event3);
    }
}
