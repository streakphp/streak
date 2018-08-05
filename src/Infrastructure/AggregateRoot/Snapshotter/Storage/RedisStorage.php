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

use Redis;
use Streak\Domain\AggregateRoot;
use Streak\Infrastructure\AggregateRoot\Snapshotter\Storage;
use Streak\Infrastructure\AggregateRoot\Snapshotter\Storage\Exception\SnapshotNotFound;
use Streak\Infrastructure\Resettable;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class RedisStorage implements Storage, Resettable
{
    private $redis;

    public function __construct(Redis $client)
    {
        $this->redis = $client;
    }

    public function find(AggregateRoot $aggregate) : ?string
    {
        $snasphot = $this->redis->get($this->key($aggregate));

        if (false === $snasphot) {
            throw new SnapshotNotFound($aggregate);
        }

        return $snasphot;
    }

    public function store(AggregateRoot $aggregate, $snapshot)
    {
        $this->redis->set($this->key($aggregate), (string) $snapshot);
    }

    public function reset() : bool
    {
        return $this->redis->flushDB();
    }

    private function key(AggregateRoot $aggregate) : string
    {
        return
            get_class($aggregate).
            get_class($aggregate->aggregateRootId()).
            $aggregate->aggregateRootId()->toString()
        ;
    }
}
