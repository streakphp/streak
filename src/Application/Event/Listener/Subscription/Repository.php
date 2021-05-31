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

namespace Streak\Application\Event\Listener\Subscription;

use Streak\Domain\Event;
use Streak\Domain\Exception;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Repository
{
    /**
     * @throws Exception\ObjectNotSupported
     */
    public function find(\Streak\Application\Event\Listener\Id $id): ?\Streak\Application\Event\Listener\Subscription;

    /**
     * @throws Exception\ObjectNotSupported
     */
    public function has(\Streak\Application\Event\Listener\Subscription $subscription): bool;

    /**
     * @throws Exception\ObjectNotSupported
     */
    public function add(\Streak\Application\Event\Listener\Subscription $subscription): void;

    /**
     * @return \Streak\Application\Event\Listener\Subscription[]|iterable
     */
    public function all(?Repository\Filter $filter = null): iterable;
}
