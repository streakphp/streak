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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\AggregateRoot;
use Streak\Infrastructure\AggregateRoot\Snapshotter\Storage\Exception\SnapshotNotFound;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\AggregateRoot\Snapshotter\Storage\RedisStorage
 */
class RedisStorageTest extends TestCase
{
    /**
     * @var \Redis
     */
    private $redis;

    /**
     * @var AggregateRoot|MockObject
     */
    private $aggregate1;

    /**
     * @var AggregateRoot\Id|MockObject
     */
    private $aggregateId1;

    /**
     * @var AggregateRoot|MockObject
     */
    private $aggregate2;

    /**
     * @var AggregateRoot\Id|MockObject
     */
    private $aggregateId2;

    protected function setUp()
    {
        $this->redis = new \Redis();
        $this->redis->connect($_ENV['PHPUNIT_REDIS_HOST'], (int) $_ENV['PHPUNIT_REDIS_PORT']);
        $this->redis->select((int) $_ENV['PHPUNIT_REDIS_DATABASE']);

        $this->aggregate1 = $this->getMockBuilder(AggregateRoot::class)->setMockClassName('streak__aggregate_1')->getMockForAbstractClass();
        $this->aggregateId1 = $this->getMockBuilder(AggregateRoot\Id::class)->setMockClassName('streak__aggregate_id_1')->getMockForAbstractClass();
        $this->aggregate2 = $this->getMockBuilder(AggregateRoot::class)->setMockClassName('streak__aggregate_2')->getMockForAbstractClass();
        $this->aggregateId2 = $this->getMockBuilder(AggregateRoot\Id::class)->setMockClassName('streak__aggregate_id_2')->getMockForAbstractClass();
    }

    public function testObject()
    {
        $storage = new RedisStorage($this->redis);

        $storage->store($this->aggregate1, 'snapshot-1');

        $result = $storage->find($this->aggregate1);

        $this->assertSame('snapshot-1', $result);

        $result = $storage->find($this->aggregate1);

        $this->assertSame('snapshot-1', $result);

        $storage->store($this->aggregate1, 'snapshot-2');

        $result = $storage->find($this->aggregate1);

        $this->assertSame('snapshot-2', $result);

        $result = $storage->find($this->aggregate1);

        $this->assertSame('snapshot-2', $result);

        $this->expectExceptionObject(new SnapshotNotFound($this->aggregate1));

        $storage->find($this->aggregate2);
    }

    public function testResetting()
    {
        $storage = new RedisStorage($this->redis);

        $storage->store($this->aggregate1, 'snapshot-1');

        $result = $storage->find($this->aggregate1);

        $this->assertSame('snapshot-1', $result);

        $storage->reset();

        $this->expectExceptionObject(new SnapshotNotFound($this->aggregate1));

        $storage->find($this->aggregate1);
    }
}
