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
use Streak\Infrastructure\Domain\AggregateRoot\Snapshotter;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
final class InMemoryStorage implements Snapshotter\Storage
{
    private array $snapshots = [];

    /**
     * @throws Exception\SnapshotNotFound
     */
    public function find(AggregateRoot $aggregate): string
    {
        foreach ($this->snapshots as [$id, $snapshot]) {
            if ($aggregate->id()->equals($id)) {
                return $snapshot;
            }
        }

        throw new Exception\SnapshotNotFound($aggregate);
    }

    public function store(AggregateRoot $aggregate, string $newSnapshot): void
    {
        foreach ($this->snapshots as $key => [$id, $snapshot]) {
            if ($aggregate->id()->equals($id)) {
                $this->snapshots[$key] = [$id, $newSnapshot];

                return;
            }
        }

        $this->snapshots[] = [$aggregate->id(), $newSnapshot];
    }
}
