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

use Streak\Domain\EventStore;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\EventStore\InMemoryEventStore
 * @covers \Streak\Infrastructure\Event\InMemoryStream
 */
class InMemoryEventStoreTest extends EventStoreTestCase
{
    public function testClear()
    {
        $store = new InMemoryEventStore();

        $producer11 = new EventStoreTestCase\ProducerId1('producer1-1');
        $producer12 = new EventStoreTestCase\ProducerId1('producer1-2');

        $event1 = new EventStoreTestCase\Event1();
        $event2 = new EventStoreTestCase\Event2();
        $event3 = new EventStoreTestCase\Event3();
        $event4 = new EventStoreTestCase\Event4();

        $this->assertEquals([], iterator_to_array($store->stream()));

        $store->add($producer11, null, $event1, $event2);
        $this->assertEquals([$event1, $event2], iterator_to_array($store->stream()));

        $store->add($producer12, null, $event3, $event4);
        $this->assertEquals([$event1, $event2, $event3, $event4], iterator_to_array($store->stream()));

        $store->clear();

        $this->assertEquals([], iterator_to_array($store->stream()));
    }

    protected function newEventStore() : EventStore
    {
        $store = new InMemoryEventStore();

        return $store;
    }
}
