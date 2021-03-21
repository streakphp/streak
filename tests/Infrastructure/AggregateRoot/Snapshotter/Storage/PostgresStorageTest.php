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

namespace Streak\Infrastructure\AggregateRoot\Snapshotter\Storage;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\MockObject\MockObject;
use Streak\Domain;
use Streak\Domain\AggregateRoot;
use Streak\Infrastructure\AggregateRoot\Snapshotter\Storage\Exception\SnapshotNotFound;

/**
 * @covers \Streak\Infrastructure\AggregateRoot\Snapshotter\Storage\PostgresStorage
 */
final class PostgresStorageTest extends \PHPUnit\Framework\TestCase
{
    private static ?Connection $connection = null;

    private ?PostgresStorage $storage = null;

    public static function setUpBeforeClass() : void
    {
        self::$connection = DriverManager::getConnection(
            [
                'driver' => 'pdo_pgsql',
                'host' => $_ENV['PHPUNIT_POSTGRES_HOSTNAME'],
                'port' => (int) $_ENV['PHPUNIT_POSTGRES_PORT'],
                'dbname' => $_ENV['PHPUNIT_POSTGRES_DATABASE'],
                'user' => $_ENV['PHPUNIT_POSTGRES_USERNAME'],
                'password' => $_ENV['PHPUNIT_POSTGRES_PASSWORD'],
            ]
        );
    }

    public function setUp() : void
    {
        parent::setUp();
        $this->storage = new PostgresStorage(self::$connection);
        $this->givenSnapshotsTableDoesNotExists();
    }

    public function testItStoresUsingInsert() : void
    {
        $id = '3e7c8ffa-6bc6-4070-a6b5-30f9ae1c06fe';
        $stub = $this->createAggregateRootStub($id);
        $this->storage->store($stub, 'snapshot');
        self::assertEquals(
            [
                'id' => '3e7c8ffa-6bc6-4070-a6b5-30f9ae1c06fe',
                'type' => 'Streak\\Infrastructure\\AggregateRoot\\Snapshotter\\Storage\\IdStub',
                'snapshot' => 'snapshot',
                'snapshot_size' => 8,
                'stores_counter' => 1,
            ],
            $this->fetchSnapshotRecord($id)
        );
    }

    public function testItStoresUsingUpdate() : void
    {
        $id = '3e7c8ffa-6bc6-4070-a6b5-30f9ae1c06fe';
        $this->storage->reset();
        $this->givenThereIsSnapshot([
            'id' => $id,
            'type' => IdStub::class,
            'snapshot' => 'snapshot',
            'snapshot_size' => 8,
            'stores_counter' => 1,
        ]);
        $stub = $this->createAggregateRootStub($id);
        $this->storage->store($stub, 'changed_snapshot');

        self::assertEquals(
            [
                'id' => '3e7c8ffa-6bc6-4070-a6b5-30f9ae1c06fe',
                'type' => 'Streak\\Infrastructure\\AggregateRoot\\Snapshotter\\Storage\\IdStub',
                'snapshot' => 'changed_snapshot',
                'snapshot_size' => 16,
                'stores_counter' => 2,
            ],
            $this->fetchSnapshotRecord($id)
        );
    }

    public function testIsStoresWhenTableDoesNotExist() : void
    {
        $this->givenSnapshotsTableDoesNotExists();
        $id = '3e7c8ffa-6bc6-4070-a6b5-30f9ae1c06fe';

        $this->storage->store($this->createAggregateRootStub($id), 'snapshot');
        self::assertNotEmpty($this->fetchSnapshotRecord($id));
    }

    public function testItFinds() : void
    {
        $this->givenSnapshotsTableExists();
        $id = '3e7c8ffa-6bc6-4070-a6b5-30f9ae1c06fe';
        $this->givenThereIsSnapshot([
            'id' => $id,
            'type' => IdStub::class,
            'snapshot' => 'snapshot',
            'created_at' => '2020-01-14 04:31:17.031406+00',
            'stores_counter' => 1,
            'snapshot_size' => 8,
        ]);

        $snapshot = $this->storage->find($this->createAggregateRootStub($id));
        self::assertEquals('snapshot', $snapshot);
    }

    public function testItDoesntFindWhenTableDoesNotExists() : void
    {
        $this->givenSnapshotsTableDoesNotExists();
        self::expectException(SnapshotNotFound::class);
        $this->storage->find($this->createAggregateRootStub('3e7c8ffa-6bc6-4070-a6b5-30f9ae1c06fe'));
    }

    public function testItDoesntFindWhenRowDoesNotExist() : void
    {
        $this->givenSnapshotsTableExists();
        self::expectException(SnapshotNotFound::class);
        $this->storage->find($this->createAggregateRootStub('3e7c8ffa-6bc6-4070-a6b5-30f9ae1c06fe'));
    }

    public function testItResets() : void
    {
        self::assertTrue($this->storage->reset());
    }

    private function fetchSnapshotRecord(string $id) : array
    {
        $sql = 'SELECT id, type, snapshot, snapshot_size, stores_counter FROM snapshots WHERE id = :id';

        return self::$connection->fetchAssoc($sql, ['id' => $id]);
    }

    private function givenThereIsSnapshot(array $fixture) : void
    {
        self::$connection->insert('snapshots', $fixture);
    }

    private function givenSnapshotsTableDoesNotExists() : void
    {
        self::$connection->exec('DROP TABLE IF EXISTS snapshots');
    }

    private function givenSnapshotsTableExists() : void
    {
        $this->storage->reset();
    }

    private function createAggregateRootStub(string $id) : AggregateRoot
    {
        $id = new IdStub($id);
        /** @var AggregateRoot|MockObject $stub */
        $stub = $this->getMockBuilder(AggregateRoot::class)->getMock();
        $stub->method('id')->willReturn($id);

        return $stub;
    }
}

class IdStub implements AggregateRoot\Id
{
    private string $id;

    /**
     * IdMock constructor.
     *
     * @param $id
     */
    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function equals($object) : bool
    {
        return true;
    }

    public function toString() : string
    {
        return $this->id;
    }

    public static function fromString(string $id) : Domain\Id
    {
        return new self($id);
    }
}
