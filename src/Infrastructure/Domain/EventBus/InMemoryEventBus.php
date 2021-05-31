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

namespace Streak\Infrastructure\Domain\EventBus;

use Streak\Domain\Event;
use Streak\Domain\EventBus;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Domain\EventBus\InMemoryEventBusTest
 */
class InMemoryEventBus implements EventBus
{
    /**
     * @var \Streak\Application\Event\Listener[]
     */
    private \SplObjectStorage $listeners;

    /**
     * @var Event[]
     */
    private array $events = [];

    private bool $publishing = false;

    public function __construct()
    {
        $this->listeners = new \SplObjectStorage();
    }

    public function add(\Streak\Application\Event\Listener $listener): void
    {
        $this->listeners->attach($listener);
    }

    public function remove(\Streak\Application\Event\Listener $listener): void
    {
        $this->listeners->detach($listener);
    }

    public function publish(Event\Envelope ...$events): void
    {
        if (0 === \count($events)) {
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
