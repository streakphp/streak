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
use Streak\Infrastructure\AggregateRoot\Snapshotter\Storage\Exception;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Storage
{
    /**
     * @throws Exception\SnapshotNotFound
     */
    public function find(AggregateRoot $aggregate): string;

    public function store(AggregateRoot $aggregate, string $snapshot): void;
}
