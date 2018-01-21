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
 */
class SubscriptionListenedToEvent implements Event
{
    private $event;

    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    public function event() : Event
    {
        return $this->event;
    }
}
