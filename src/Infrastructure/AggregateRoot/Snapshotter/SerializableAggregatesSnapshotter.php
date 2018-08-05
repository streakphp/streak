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

namespace Streak\Infrastructure\AggregateRoot\Snapshotter;

use Streak\Domain\AggregateRoot;
use Streak\Infrastructure\AggregateRoot\Snapshotter;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class SerializableAggregatesSnapshotter implements Snapshotter
{
    private $storage;

    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
    }

    public function restoreToSnapshot(AggregateRoot $aggregate) : AggregateRoot
    {
        if (!$aggregate instanceof \Serializable) {
            return $aggregate;
        }

        try {
            $serialized = $this->storage->find($aggregate);
        } catch (Snapshotter\Storage\Exception\SnapshotNotFound $e) {
            return $e->aggregate();
        }

        $aggregate->unserialize($serialized);

        return $aggregate;
    }

    public function takeSnapshot(AggregateRoot $aggregate) : AggregateRoot
    {
        if (!$aggregate instanceof \Serializable) {
            return $aggregate;
        }

        $serialized = $aggregate->serialize();

        $this->storage->store($aggregate, $serialized);

        return $aggregate;
    }
}
