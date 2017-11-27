<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain;

use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface EventStore
{
    /**
     * @throws Exception\ConcurrentWriteDetected
     * @throws Exception\InvalidAggregateGiven
     */
    public function add(Domain\Event ...$events) : void;

    /**
     * @return Domain\Event[]
     *
     * @throws Exception\InvalidAggregateGiven
     */
    public function find(Domain\AggregateRootId $id) : array;
}
