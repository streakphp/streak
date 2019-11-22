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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use PHPUnit\Framework\MockObject\MockObject;
use Streak\Domain\Event;
use Streak\Domain\EventStore;
use Streak\Domain\Exception\ConcurrentWriteDetected;
use Streak\Infrastructure\Event\Converter\FlatObjectConverter;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\EventStore\DbalPostgresEventStore
 */
class DbalPostgresEventStoreTest extends EventStoreTestCase
{
    /**
     * @var Connection
     */
    private static $connection1;

    /**
     * @var Connection
     */
    private static $connection2;

    /**
     * @var Connection|MockObject
     */
    private $mysql;

    /**
     * @var MySqlPlatform
     */
    private $mysqlPlatform;

    public static function setUpBeforeClass()
    {
        self::$connection1 = DriverManager::getConnection([
            'driver' => 'pdo_pgsql',
            'host' => $_ENV['PHPUNIT_POSTGRES_HOSTNAME'],
            'port' => (int) $_ENV['PHPUNIT_POSTGRES_PORT'],
            'dbname' => $_ENV['PHPUNIT_POSTGRES_DATABASE'],
            'user' => $_ENV['PHPUNIT_POSTGRES_USERNAME'],
            'password' => $_ENV['PHPUNIT_POSTGRES_PASSWORD'],
        ]);
        self::$connection2 = DriverManager::getConnection([
            'driver' => 'pdo_pgsql',
            'host' => $_ENV['PHPUNIT_POSTGRES_HOSTNAME'],
            'port' => (int) $_ENV['PHPUNIT_POSTGRES_PORT'],
            'dbname' => $_ENV['PHPUNIT_POSTGRES_DATABASE'],
            'user' => $_ENV['PHPUNIT_POSTGRES_USERNAME'],
            'password' => $_ENV['PHPUNIT_POSTGRES_PASSWORD'],
        ]);
    }

    protected function setUp()
    {
        parent::setUp();

        $this->mysql = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
        $this->mysqlPlatform = new MySqlPlatform();
    }

    public function testPlatformCheckWhenCreatingStore()
    {
        $expected = new \RuntimeException('Only PostgreSQL database is supported by selected event store.');
        $this->expectExceptionObject($expected);

        $store = new DbalPostgresEventStore($this->mysql, new FlatObjectConverter());

        $this->mysql
            ->expects($this->once())
            ->method('getDatabasePlatform')
            ->with()
            ->willReturn($this->mysqlPlatform)
        ;

        $this->mysql
            ->expects($this->never())
            ->method($this->logicalNot($this->equalTo('getDatabasePlatform')))
        ;

        $store->create();
    }

    public function testPlatformCheckWhenDropingStore()
    {
        $expected = new \RuntimeException('Only PostgreSQL database is supported by selected event store.');
        $this->expectExceptionObject($expected);

        $store = new DbalPostgresEventStore($this->mysql, new FlatObjectConverter());

        $this->mysql
            ->expects($this->once())
            ->method('getDatabasePlatform')
            ->with()
            ->willReturn($this->mysqlPlatform)
        ;

        $this->mysql
            ->expects($this->never())
            ->method($this->logicalNot($this->equalTo('getDatabasePlatform')))
        ;

        $store->drop();
    }

    /**
     * this time two separate connections are used.
     *
     * @see EventStoreTestCase::testConcurrentWriting()
     */
    public function testConcurrentWriting2()
    {
        $producerId1 = new EventStoreTestCase\ProducerId1('producer1');
        $event1 = new EventStoreTestCase\Event1();
        $event1 = Event\Envelope::new($event1, $producerId1, 1);
        $event2 = new EventStoreTestCase\Event2();
        $event2 = Event\Envelope::new($event2, $producerId1, 2);
        $event3 = new EventStoreTestCase\Event3();
        $event3 = Event\Envelope::new($event3, $producerId1, 1);
        $event4 = new EventStoreTestCase\Event4();
        $event4 = Event\Envelope::new($event4, $producerId1, 2);

        $store1 = new DbalPostgresEventStore(self::$connection2, new FlatObjectConverter());
        $store1->add($event1, $event2);

        $store2 = new DbalPostgresEventStore(self::$connection1, new FlatObjectConverter());

        $this->expectExceptionObject(new ConcurrentWriteDetected($producerId1));

        try {
            $store2->add($event3, $event4);
        } catch (ConcurrentWriteDetected $e) {
            // test that no events were added
            $this->assertEquals([$event1, $event2], iterator_to_array($store2->stream()));

            throw $e;
        }
    }

    /**
     * this time two separate connections are used.
     *
     * @see EventStoreTestCase::testConcurrentWriting()
     */
    public function testWritingWhenNoTableInDatabase()
    {
        $producerId1 = new EventStoreTestCase\ProducerId1('producer1');
        $event1 = new EventStoreTestCase\Event1();
        $event1 = Event\Envelope::new($event1, $producerId1, 1);
        $event2 = new EventStoreTestCase\Event2();
        $event2 = Event\Envelope::new($event2, $producerId1, 2);

        $store1 = new DbalPostgresEventStore(self::$connection2, new FlatObjectConverter());
        $store1->drop();

        $this->expectException(DBALException::class);

        $store2 = new DbalPostgresEventStore(self::$connection1, new FlatObjectConverter());
        $store2->add($event1, $event2);
    }

    public function testSchema()
    {
        $store = new DbalPostgresEventStore($this->mysql, new FlatObjectConverter());

        $schema = $store->schema();

        $this->assertSame($store, $schema);
    }

    protected function newEventStore() : EventStore
    {
        $store = new DbalPostgresEventStore(self::$connection1, new FlatObjectConverter());
        $store->drop();
        $store->create();

        return $store;
    }
}
