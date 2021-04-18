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

use Streak\Infrastructure\AggregateRoot\Snapshotter\Storage;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\AggregateRoot\Snapshotter\Storage\InMemoryStorage
 * @covers \Streak\Infrastructure\AggregateRoot\Snapshotter\Storage\TestCase
 */
class InMemoryTest extends TestCase
{
    protected function newStorage(): Storage
    {
        return new InMemoryStorage();
    }
}
