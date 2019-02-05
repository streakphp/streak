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
use Streak\Domain\Id\Uuid;
use Streak\Domain\Id\Uuid\Uuid4Factory;
use Streak\Infrastructure\EventBus\EventStoreTestCase\Event1;
use Streak\Infrastructure\EventBus\EventStoreTestCase\Event2;
use Streak\Infrastructure\EventBus\EventStoreTestCase\Event3;
use Streak\Infrastructure\EventBus\EventStoreTestCase\Event4;
use Streak\Infrastructure\EventBus\EventStoreTestCase\ProducerId1;
use Streak\Infrastructure\Id\Uuid\TestUuid4Factory;

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
        $store = new InMemoryEventStore(new TestUuid4Factory(
            new Uuid('bbe068fb-59ac-48d2-99ff-ff98f604f863'),
            new Uuid('74a399bf-9007-4d3a-b0cc-a4ba0ccb6fbe'),
            new Uuid('aa8496a4-b47b-4f55-ba00-da1d1ebffde9'),
            new Uuid('2e8fc646-a01a-4d0a-b6e6-38bb52c063e0')
        ));

        $producer11 = new ProducerId1('producer1-1');
        $producer12 = new ProducerId1('producer1-2');

        $event1 = new Event1();
        $event2 = new Event2();
        $event3 = new Event3();
        $event4 = new Event4();

        $this->assertEquals([], iterator_to_array($store->stream()));

        $store->add($producer11, null, $event1, $event2);
        $this->assertEquals([$event1, $event2], iterator_to_array($store->stream()));

        $store->add($producer12, null, $event3, $event4);
        $this->assertEquals([$event1, $event2, $event3, $event4], iterator_to_array($store->stream()));

        $store->clear();

        $this->assertEquals([], iterator_to_array($store->stream()));
    }

    protected function newEventStore(Uuid4Factory $factory) : EventStore
    {
        $store = new InMemoryEventStore($factory);

        return $store;
    }
}
