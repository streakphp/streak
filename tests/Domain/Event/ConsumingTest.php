<?php

/*
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
use Streak\Domain\Event\ConsumingTest\ConsumerStub;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Consuming
 */
class ConsumingTest extends TestCase
{
    public function testConsuming()
    {
        $consumer = new ConsumerStub();

        $this->assertEmpty($consumer->consumed());
        $this->assertNull($consumer->lastReplayed());

        $event1 = $this->getMockBuilder(Domain\Event::class)->getMockForAbstractClass();
        $event2 = $this->getMockBuilder(Domain\Event::class)->getMockForAbstractClass();
        $event3 = $this->getMockBuilder(Domain\Event::class)->getMockForAbstractClass();
        $event4 = $this->getMockBuilder(Domain\Event::class)->getMockForAbstractClass();

        $events = [$event1, $event2, $event3, $event4];

        $consumer->replay(...$events);

        $this->assertEquals($events, $consumer->consumed());
        $this->assertEquals($event4, $consumer->lastReplayed());
    }
}

namespace Streak\Domain\Event\ConsumingTest;

use Streak\Domain;
use Streak\Domain\Event;

class ConsumerStub
{
    use Event\Consuming;

    private $consumed = [];

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
}
