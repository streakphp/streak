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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\Id\UUID;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\EventBus\InMemoryEventBus
 */
class InMemoryEventBusTest extends TestCase
{
    private Event\Listener|MockObject $listener1;
    private Event\Listener|MockObject $listener2;
    private Event\Listener|MockObject $listener3;

    private Event\Listener\Id $listenerId1;
    private Event\Listener\Id $listenerId2;
    private Event\Listener\Id $listenerId3;

    private Event\Envelope $event1;
    private Event\Envelope $event2;
    private Event\Envelope $event3;

    protected function setUp(): void
    {
        $this->listenerId1 = new class ('98f94b04-e5e9-4032-aaa4-b3a89cfa69a8') extends UUID implements Event\Listener\Id {};
        $this->listenerId2 = new class ('90b13520-4ce0-4105-a054-b033748a56cc') extends UUID implements Event\Listener\Id {};
        $this->listenerId3 = new class ('8a7bbab7-8e41-4741-972a-f4413a3cb49f') extends UUID implements Event\Listener\Id {};

        $this->listener1 = $this->getMockBuilder(Event\Listener::class)->setMockClassName('listener1_2783trn')->getMockForAbstractClass();
        $this->listener1->expects(self::any())->method('id')->willReturn($this->listenerId1);
        $this->listener2 = $this->getMockBuilder(Event\Listener::class)->setMockClassName('listener2_qw6rh33')->getMockForAbstractClass();
        $this->listener2->expects(self::any())->method('id')->willReturn($this->listenerId2);
        $this->listener3 = $this->getMockBuilder(Event\Listener::class)->setMockClassName('listener3_jasge7b')->getMockForAbstractClass();
        $this->listener3->expects(self::any())->method('id')->willReturn($this->listenerId3);

        $this->event1 = new Event\Envelope(UUID::fromString('e7dd3e20-d3bc-4722-a601-57c73c4f5452'), 'event1', $this->getMockBuilder(Event::class)->setMockClassName('event1_yuwgrt2r')->getMockForAbstractClass(), $id = UUID::random(), $id);
        $this->event2 = new Event\Envelope(UUID::fromString('4a2d2323-9841-4e8d-a9e5-224beb6f36d7'), 'event2', $this->getMockBuilder(Event::class)->setMockClassName('event2_237trxbq')->getMockForAbstractClass(), $id = UUID::random(), $id);
        $this->event3 = new Event\Envelope(UUID::fromString('e4339858-831a-40fa-a35b-4690329890f7'), 'event3', $this->getMockBuilder(Event::class)->setMockClassName('event3_fhsyrt23')->getMockForAbstractClass(), $id = UUID::random(), $id);
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
        ;

        $bus->publish($this->event1);

        $this->listener2
            ->method('on')
            ->withConsecutive(
                [$this->event2],
                [$this->event3],
            )->willReturnOnConsecutiveCalls(
                self::returnCallback(function () use ($bus) {
                    $bus->remove($this->listener1);
                    $bus->add($this->listener3);
                    $bus->publish($this->event3);

                    return true;
                }),
                true,
            );

        $this->listener3
            ->method('on')
            ->withConsecutive(
                [$this->event2],
                [$this->event3]
            )
        ;

        $bus->add($this->listener2);

        $bus->publish($this->event2); // listener2 published event3, added listener3 and removed listener1

        $events = [];
        $bus->publish(...$events);
    }
}
