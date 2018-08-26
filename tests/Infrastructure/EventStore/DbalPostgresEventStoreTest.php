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
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use PHPUnit\Framework\MockObject\MockObject;
use Streak\Domain\EventStore;
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
    private static $postgres;

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
        self::$postgres = DriverManager::getConnection([
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

    public function testSchema()
    {
        $store = new DbalPostgresEventStore($this->mysql, new FlatObjectConverter());

        $schema = $store->schema();

        $this->assertSame($store, $schema);
    }

    protected function newEventStore() : EventStore
    {
        $store = new DbalPostgresEventStore(self::$postgres, new FlatObjectConverter());
        $store->drop();
        $store->create();

        return $store;
    }
}
