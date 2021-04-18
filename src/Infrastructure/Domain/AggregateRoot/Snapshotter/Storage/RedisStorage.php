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

use Streak\Domain\AggregateRoot;
use Streak\Infrastructure\Domain\AggregateRoot\Snapshotter\Storage;
use Streak\Infrastructure\Domain\AggregateRoot\Snapshotter\Storage\Exception\SnapshotNotFound;
use Streak\Infrastructure\Domain\Resettable;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
final class RedisStorage implements Storage, Resettable
{
    private \Redis $redis;

    public function __construct(\Redis $client)
    {
        $this->redis = $client;
    }

    /**
     * @throws Exception\SnapshotNotFound
     */
    public function find(AggregateRoot $aggregate): string
    {
        $snapshot = $this->redis->get($this->key($aggregate));

        if (false === $snapshot) {
            throw new SnapshotNotFound($aggregate);
        }

        return $snapshot;
    }

    public function store(AggregateRoot $aggregate, string $snapshot): void
    {
        $this->redis->set($this->key($aggregate), (string) $snapshot);
    }

    public function reset(): bool
    {
        return $this->redis->flushDB();
    }

    private function key(AggregateRoot $aggregate): string
    {
        return
            \get_class($aggregate).
            \get_class($aggregate->aggregateRootId()).
            $aggregate->aggregateRootId()->toString()
        ;
    }
}
