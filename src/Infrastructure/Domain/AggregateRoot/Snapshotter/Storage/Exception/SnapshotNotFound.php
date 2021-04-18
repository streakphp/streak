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

namespace Streak\Infrastructure\Domain\AggregateRoot\Snapshotter\Storage\Exception;

use Streak\Domain\AggregateRoot;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Domain\AggregateRoot\Snapshotter\Storage\Exception\SnapshotNotFoundTest
 */
class SnapshotNotFound extends \RuntimeException
{
    private AggregateRoot $aggregate;

    public function __construct(AggregateRoot $aggregate)
    {
        $this->aggregate = $aggregate;

        parent::__construct(sprintf('Snapshot for aggregate "%s#%s" not found.', \get_class($this->aggregate->aggregateRootId()), $this->aggregate->aggregateRootId()->toString()));
    }

    public function aggregate(): AggregateRoot
    {
        return $this->aggregate;
    }
}
