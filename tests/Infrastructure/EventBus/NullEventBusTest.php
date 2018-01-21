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

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\EventBus\InMemoryEventBus
 */
class NullEventBusTest extends TestCase
{
    /**
     * @var Event\Listener|\PHPUnit_Framework_MockObject_MockObject
     */
    private $listener1;

    /**
     * @var Event\Listener|\PHPUnit_Framework_MockObject_MockObject
     */
    private $listener2;

    /**
     * @var Event\Listener|\PHPUnit_Framework_MockObject_MockObject
     */
    private $listener3;

    /**
     * @var Event|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event1;

    /**
     * @var Event|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event2;

    /**
     * @var Event|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event3;

    protected function setUp()
    {
        $this->listener1 = $this->getMockBuilder(Event\Listener::class)->setMockClassName('listener1')->getMockForAbstractClass();
        $this->listener2 = $this->getMockBuilder(Event\Listener::class)->setMockClassName('listener2')->getMockForAbstractClass();
        $this->listener3 = $this->getMockBuilder(Event\Listener::class)->setMockClassName('listener3')->getMockForAbstractClass();

        $this->event1 = $this->getMockBuilder(Event::class)->setMockClassName('event1')->getMockForAbstractClass();
        $this->event2 = $this->getMockBuilder(Event::class)->setMockClassName('event2')->getMockForAbstractClass();
        $this->event3 = $this->getMockBuilder(Event::class)->setMockClassName('event3')->getMockForAbstractClass();
    }

    public function testBus()
    {
        $bus = new NullEventBus();

        $bus->add($this->listener1);
        $bus->add($this->listener1); // should be ignored

        $this->listener1
            ->expects($this->never())
            ->method('on')
        ;

        $this->listener2
            ->expects($this->never())
            ->method('on')
        ;

        $this->listener3
            ->expects($this->never())
            ->method('on')
        ;

        $bus->publish($this->event1);

        $bus->add($this->listener2);

        $bus->publish($this->event2);

        $bus->remove($this->listener1);

        $bus->publish($this->event3);
    }
}
