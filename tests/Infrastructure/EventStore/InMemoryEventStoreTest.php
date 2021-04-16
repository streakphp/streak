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

namespace Streak\Infrastructure\EventStore;

use Streak\Domain\Event;
use Streak\Domain\EventStore;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Event\InMemoryStream
 * @covers \Streak\Infrastructure\EventStore\InMemoryEventStore
 */
class InMemoryEventStoreTest extends EventStoreTestCase
{
    public function testClear(): void
    {
        $store = new InMemoryEventStore();

        $producer11 = new EventStoreTestCase\ProducerId1('producer1-1');
        $producer12 = new EventStoreTestCase\ProducerId1('producer1-2');

        $event1 = new EventStoreTestCase\Event1();
        $event1 = Event\Envelope::new($event1, $producer11, null);
        $event2 = new EventStoreTestCase\Event2();
        $event2 = Event\Envelope::new($event2, $producer11, null);
        $event3 = new EventStoreTestCase\Event3();
        $event3 = Event\Envelope::new($event3, $producer12, null);
        $event4 = new EventStoreTestCase\Event4();
        $event4 = Event\Envelope::new($event4, $producer12, null);

        self::assertEquals([], iterator_to_array($store->stream()));

        $store->add($event1, $event2);
        self::assertEquals([$event1, $event2], iterator_to_array($store->stream()));

        $store->add($event3, $event4);
        self::assertEquals([$event1, $event2, $event3, $event4], iterator_to_array($store->stream()));

        $store->clear();

        self::assertEquals([], iterator_to_array($store->stream()));
    }

    protected function newEventStore(): EventStore
    {
        return new InMemoryEventStore();
    }
}
