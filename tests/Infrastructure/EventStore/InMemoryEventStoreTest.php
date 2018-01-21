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
use Streak\Infrastructure\EventBus\PDOPostgresEventStoreTest\Event1;
use Streak\Infrastructure\EventBus\PDOPostgresEventStoreTest\Event2;
use Streak\Infrastructure\EventBus\PDOPostgresEventStoreTest\Event3;
use Streak\Infrastructure\EventBus\PDOPostgresEventStoreTest\Event4;
use Streak\Infrastructure\EventBus\PDOPostgresEventStoreTest\ProducerId1;

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

        $producer11 = new ProducerId1('producer1-1');
        $producer12 = new ProducerId1('producer1-2');

        $event1 = new Event1();
        $event2 = new Event2();
        $event3 = new Event3();
        $event4 = new Event4();

        $this->assertEquals([], iterator_to_array($store));

        $store->add($producer11, null, $event1, $event2);
        $this->assertEquals([$event1, $event2], iterator_to_array($store));

        $store->add($producer12, null, $event3, $event4);
        $this->assertEquals([$event1, $event2, $event3, $event4], iterator_to_array($store));

        $store->clear();

        $this->assertEquals([], iterator_to_array($store));
    }

    protected function newEventStore() : EventStore
    {
        $store = new InMemoryEventStore();

        return $store;
    }
}
