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
use Streak\Domain\Event;
use Streak\Domain\Id;
use Streak\Event\ConsumingTest\ConsumerStub;
use Streak\Infrastructure\Event\InMemoryStream;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Consuming
 */
class ConsumingTest extends TestCase
{
    public function testConsuming(): void
    {
        $consumer = new ConsumerStub();

        self::assertEmpty($consumer->consumed());
        self::assertNull($consumer->lastReplayed());

        $producer1 = $this->getMockBuilder(Id::class)->getMockForAbstractClass();

        $event1 = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $event1 = Event\Envelope::new($event1, $producer1);
        $event2 = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $event2 = Event\Envelope::new($event2, $producer1);
        $event3 = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $event3 = Event\Envelope::new($event3, $producer1);
        $event4 = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $event4 = Event\Envelope::new($event4, $producer1);

        $events = [$event1, $event2, $event3, $event4];

        $consumer->replay(new InMemoryStream(...$events));

        self::assertEquals($events, $consumer->consumed());
        self::assertEquals($event4, $consumer->lastReplayed());
    }
}

namespace Streak\Event\ConsumingTest;

use Streak\Domain\Event;

class ConsumerStub
{
    use Event\Consuming;

    private array $consumed = [];

    public function on(Event\Envelope $event): bool
    {
        $this->consumed[] = $event;

        return true;
    }

    /**
     * @return Event[]
     */
    public function consumed(): array
    {
        return $this->consumed;
    }
}
