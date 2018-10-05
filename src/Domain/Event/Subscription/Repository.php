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

namespace Streak\Domain\Event\Subscription;

use Streak\Domain;
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
    public function find(Domain\Id $id) : ?Event\Subscription;

    /**
     * @throws Exception\ObjectNotSupported
     */
    public function has(Event\Subscription $subscription) : bool;

    /**
     * @throws Exception\ObjectNotSupported
     */
    public function add(Event\Subscription $subscription) : void;

    /**
     * @param null|Repository\Filter $filter
     *
     * @return iterable|Event\Subscription[]
     */
    public function all(?Repository\Filter $filter = null) : iterable;
}
