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

namespace Streak\Infrastructure\Domain\AggregateRoot\Snapshotter;

use Streak\Domain\AggregateRoot;
use Streak\Infrastructure\Domain\AggregateRoot\Snapshotter;
use Streak\Infrastructure\Domain\AggregateRoot\Snapshotter\Storage\Exception\SnapshotNotFound;
use Streak\Infrastructure\Domain\Serializer;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Domain\AggregateRoot\Snapshotter\SnapshottableAggregatesSnapshotterTest
 */
final class SnapshottableAggregatesSnapshotter implements Snapshotter
{
    public function __construct(private Serializer $serializer, private Snapshotter\Storage $storage)
    {
    }

    public function restoreToSnapshot(AggregateRoot $aggregate): ?AggregateRoot
    {
        if (!$aggregate instanceof AggregateRoot\Snapshottable) {
            return null;
        }

        try {
            $serialized = $this->storage->find($aggregate);
        } catch (SnapshotNotFound) {
            return null;
        }

        $memento = $this->serializer->unserialize($serialized);
        $aggregate->fromMemento($memento);

        return $aggregate;
    }

    public function takeSnapshot(AggregateRoot $aggregate): void
    {
        if (!$aggregate instanceof AggregateRoot\Snapshottable) {
            return;
        }

        $memento = $aggregate->toMemento();
        $serialized = $this->serializer->serialize($memento);

        $this->storage->store($aggregate, $serialized);
    }
}
