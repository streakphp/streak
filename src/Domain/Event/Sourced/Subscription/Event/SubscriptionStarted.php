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

namespace Streak\Domain\Event\Sourced\Subscription\Event;

use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @codeCoverageIgnore
 */
class SubscriptionStarted implements Event
{
    private $event;
    private $startedAt;

    public function __construct(Event $event, \DateTimeInterface $startedAt)
    {
        $this->event = $event;
        $this->startedAt = $startedAt->format(DATE_ATOM);
    }

    public function startedAt() : string
    {
        return $this->startedAt;
    }

    public function event() : Event
    {
        return $this->event;
    }
}
