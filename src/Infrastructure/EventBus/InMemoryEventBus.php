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

namespace Streak\Infrastructure\EventBus;

use Streak\Domain\Event;
use Streak\Domain\EventBus;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class InMemoryEventBus implements EventBus
{
    /**
     * @var Event\Listener[]
     */
    private $listeners = [];

    /**
     * @var Event[]
     */
    private $events = [];

    /**
     * @var bool
     */
    private $publishing = false;

    public function __construct()
    {
        $this->listeners = new \SplObjectStorage();
    }

    public function add(Event\Listener $listener) : void
    {
        $this->listeners->attach($listener);
    }

    public function remove(Event\Listener $listener) : void
    {
        $this->listeners->detach($listener);
    }

    public function publish(Event\Envelope ...$events)
    {
        if (0 === count($events)) {
            return;
        }

        array_push($this->events, ...$events);

        if (false === $this->publishing) {
            $this->publishing = true;

            try {
                while ($event = array_shift($this->events)) {
                    foreach ($this->listeners as $listener) { // important that listeners loop is inside message loop as listeners can come and go between messages
                        $listener->on($event);
                    }
                }
            } finally {
                $this->publishing = false;
            }
        }
    }
}
