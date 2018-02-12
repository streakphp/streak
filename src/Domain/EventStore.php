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
    public function add(Domain\Id $producerId, ?Event $last = null, Event ...$events) : void;

    public function stream(Domain\Id ...$producers) : Event\FilterableStream;

    public function log() : Event\Log;

    /**
     * @throws Exception\EventNotInStore
     */
    public function producerId(Event $event) : Domain\Id;
}
