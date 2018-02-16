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

namespace Streak\Domain\Event;

use Streak\Domain;
use Streak\Domain\EventStore;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Subscription
{
    public function subscriptionId() : Domain\Id;

    /**
     * @param EventStore $store
     *
     * @return iterable|Domain\Event[]
     */
    public function subscribeTo(EventStore $store) : iterable;

    public function startFor(Domain\Event $event, \DateTimeInterface $startedAt); // TODO: refactor
}
