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

namespace Streak\Infrastructure\Domain\AggregateRoot;

use Streak\Domain\AggregateRoot;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Snapshotter
{
    /**
     * Returns $aggregate restored from snapshot or null if snapshot is not found or not supported.
     */
    public function restoreToSnapshot(AggregateRoot $aggregate): ?AggregateRoot;

    /**
     * Takes snapshot if $aggregate snapshotting is supported.
     */
    public function takeSnapshot(AggregateRoot $aggregate): void;
}
