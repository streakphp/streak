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

namespace Streak\Infrastructure;

use Generator;
use Streak\Domain\Event;
use Streak\Domain\Exception;
use Throwable;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface UnitOfWork
{
    public function add(Event\Producer $producer) : void;

    public function remove(Event\Producer $producer) : void;

    public function has(Event\Producer $producer) : bool;

    /**
     * @return Event\Producer[]
     */
    public function uncommitted() : array;

    public function count() : int;

    /**
     * @return Generator|Event\Producer[]
     *
     * @throws Exception\ConcurrentWriteDetected
     * @throws Throwable
     */
    public function commit() : \Generator;

    public function clear() : void;
}
