<?php

declare(strict_types=1);

/**
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Infrastructure\MessageBus;

use Streak\Domain;
use Streak\Domain\Message;
use Streak\Domain\MessageBus;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class InMemoryMessageBus implements MessageBus
{
    /**
     * @var Message\Subscriber[]
     */
    private $subscribers = [];

    /**
     * @var Message\Listener[]
     */
    private $listeners = [];

    /**
     * @var Domain\Message[]
     */
    private $messages = [];

    public function listen(Message\Listener $listener) : void
    {
        $this->listeners[] = $listener;
    }

    public function subscribe(Message\Subscriber $subscriber) : void
    {
        if (!in_array($subscriber, $this->subscribers, true)) {
            $this->subscribers[] = $subscriber;
        }
    }

    public function publish(Domain\Message ...$messages)
    {
        $this->messages = array_merge($this->messages, $messages);

        foreach ($messages as $message) {
            foreach ($this->subscribers as $subscriber) {
                try {
                    $this->listeners[] = $subscriber->createFor($message);
                } catch (\Exception $e) {
                    continue;
                }
            }
            foreach ($this->listeners as $listener) {
                $listener->on($message);
            }
        }
    }
}
