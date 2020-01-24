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

namespace Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface EventStore
{
    /**
     * @throws Exception\ConcurrentWriteDetected
     * @throws Exception\InvalidAggregateGiven
     */
    public function add(Event\Envelope ...$events) : array;

    public function stream(?EventStore\Filter $filter = null) : Event\Stream;
}
