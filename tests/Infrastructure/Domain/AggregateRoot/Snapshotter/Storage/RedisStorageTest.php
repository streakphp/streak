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

namespace Streak\Infrastructure\Domain\AggregateRoot\Snapshotter\Storage;

use Streak\Infrastructure\Domain\AggregateRoot\Snapshotter\Storage;
use Streak\Infrastructure\Domain\AggregateRoot\Snapshotter\Storage\Exception\SnapshotNotFound;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @property RedisStorage $storage
 *
 * @covers \Streak\Infrastructure\Domain\AggregateRoot\Snapshotter\Storage\RedisStorage
 */
class RedisStorageTest extends Storage\TestCase
{
    public function testResetting(): void
    {
        $this->storage->store($this->aggregate1, 'snapshot-1');

        $result = $this->storage->find($this->aggregate1);

        self::assertSame('snapshot-1', $result);

        $this->storage->reset();

        $this->expectExceptionObject(new SnapshotNotFound($this->aggregate1));

        $this->storage->find($this->aggregate1);
    }

    public function testRedisInMultiMode(): void
    {
        $redis = $this->newRedis();
        $storage = new RedisStorage($redis->multi());

        $this->expectExceptionObject(new SnapshotNotFound($this->aggregate1));

        $storage->find($this->aggregate1);
    }

    protected function newStorage(): Storage
    {
        return new RedisStorage($this->newRedis());
    }

    private function newRedis(): \Redis
    {
        $redis = new \Redis();
        $redis->connect($_ENV['PHPUNIT_REDIS_HOSTNAME'], (int) $_ENV['PHPUNIT_REDIS_PORT']);
        $redis->select((int) $_ENV['PHPUNIT_REDIS_DATABASE']);

        return $redis;
    }
}
