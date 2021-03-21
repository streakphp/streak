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

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Streak\Domain\AggregateRoot;
use Streak\Infrastructure\AggregateRoot\Snapshotter\Storage;
use Streak\Infrastructure\Resettable;

/**
 * @see \Streak\Infrastructure\AggregateRoot\Snapshotter\Storage\PostgresStorageTest
 */
class PostgresStorage implements Storage, Resettable
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function reset() : bool
    {
        $this->dropTable();
        $this->createTable();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function find(AggregateRoot $aggregate) : string
    {
        try {
            return $this->doFind($aggregate);
        } catch (TableNotFoundException $e) {
            throw new Storage\Exception\SnapshotNotFound($aggregate);
        }
    }

    public function store(AggregateRoot $aggregate, string $snapshot) : void
    {
        try {
            $this->doStore($aggregate, $snapshot);
        } catch (TableNotFoundException $e) {
            $this->createTable();
            $this->doStore($aggregate, $snapshot);
        }
    }

    private function doFind(AggregateRoot $aggregate) : string
    {
        $sql = 'SELECT snapshot FROM snapshots WHERE type = :type AND id = :id';
        $statement = $this->connection->prepare($sql);
        $statement->bindValue('type', get_class($aggregate->id()));
        $statement->bindValue('id', $aggregate->id()->toString());
        $statement->execute();
        $row = $statement->fetch();
        if (empty($row)) {
            throw new Storage\Exception\SnapshotNotFound($aggregate);
        }

        return $row['snapshot'];
    }

    private function doStore(AggregateRoot $aggregate, string $snapshot) : void
    {
        $sql = <<<SQL
INSERT INTO snapshots as t (
                id, 
                type, 
                snapshot, 
                snapshot_size,
                stores_counter
                ) 
            VALUES 
            (
                :id, 
                :type, 
                :snapshot, 
                :snapshot_size,
                :stores_counter
            )
ON CONFLICT ON CONSTRAINT snapshots_pk DO
UPDATE SET 
           snapshot = :snapshot, 
           updated_at = NOW(), 
           snapshot_size = :snapshot_size,
           stores_counter = t.stores_counter+1
SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue('id', $aggregate->id()->toString());
        $statement->bindValue('type', get_class($aggregate->id()));
        $statement->bindValue('snapshot', $snapshot);
        $statement->bindValue('snapshot_size', mb_strlen($snapshot));
        $statement->bindValue('stores_counter', 1);
        $statement->execute();
    }

    private function createTable() : void
    {
        $sql = <<<SQL
create table snapshots
(
	id uuid not null,
	type varchar(255) not null,
	snapshot text not null,
	created_at timestamp(6) with time zone not null default now(),
	updated_at timestamp(6) with time zone,
	snapshot_size int not null,
	stores_counter int not null,
	constraint snapshots_pk
		primary key (type, id)
);
SQL;
        $this->connection->exec($sql);
    }

    private function dropTable() : void
    {
        $this->connection->exec('DROP TABLE IF EXISTS snapshots');
    }
}
