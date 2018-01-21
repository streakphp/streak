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
use Streak\Domain\Event;
use Streak\Domain\EventBus;
use Streak\Domain\EventStore;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class Subscriber implements Listener
{
    private $listener;

    public function __construct(Listener $listener)
    {
        $this->listener = $listener;
    }

    public function id() : Domain\Id
    {
        return $this->listener->id();
    }

    public function subscribeTo(EventStore $store) : Subscription
    {
    }

    public function listenTo(EventBus $bus)
    {
        $bus->add($this);
    }

    public function on(Event $event) : bool
    {
    }
}
