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
class CompositeSnapshotter implements Snapshotter
{
    private $snapshotters = [];

    public function __construct(Snapshotter ...$snapshotters)
    {
        $this->snapshotters = $snapshotters;
    }

    public function restoreToSnapshot(AggregateRoot $aggregate) : AggregateRoot
    {
        foreach ($this->snapshotters as $snapshotter) {
            $aggregate = $snapshotter->restoreToSnapshot($aggregate);
        }

        return $aggregate;
    }

    public function takeSnapshot(AggregateRoot $aggregate) : AggregateRoot
    {
        foreach ($this->snapshotters as $snapshotter) {
            $aggregate = $snapshotter->takeSnapshot($aggregate);
        }

        return $aggregate;
    }
}
