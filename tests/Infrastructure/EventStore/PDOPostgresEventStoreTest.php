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
use Streak\Infrastructure\Event\Converter\FlatObjectConverter;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\EventStore\PDOPostgresEventStore
 */
class PDOPostgresEventStoreTest extends EventStoreTestCase
{
    /**
     * @var \PDO
     */
    private static $pdo;

    public static function setUpBeforeClass()
    {
        self::$pdo = new \PDO($_ENV['DATABASE_URL']);
    }

    protected function newEventStore() : EventStore
    {
        $store = new PDOPostgresEventStore(self::$pdo, new FlatObjectConverter());
        $store->drop();
        $store->create();

        return $store;
    }
}
