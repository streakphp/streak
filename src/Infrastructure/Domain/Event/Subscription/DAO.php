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

namespace Streak\Infrastructure\Domain\Event\Subscription;

use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Subscription;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface DAO
{
    public function save(Subscription $subscription): void;

    public function one(Listener\Id $id): ?Subscription;

    public function exists(Listener\Id $id): bool;

    /**
     * @param string[] $types
     *
     * @return DAO\Subscription[]
     */
    public function all(array $types = [], ?bool $completed = null): iterable;
}
