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

namespace Streak\Infrastructure\Event;

use Psr\Log;
use Streak\Domain;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class LoggingListener implements Event\Listener, Event\Replayable, Event\Process
{
    private $listener;
    private $logger;

    public function __construct(Event\Listener $listener, Log\LoggerInterface $logger)
    {
        $this->listener = $listener;
        $this->logger = $logger;
    }

    public function completed() : bool
    {
        if ($this->listener instanceof Event\Process) {
            return $this->listener->completed();
        }

        return false;
    }

    public function id() : Domain\Id
    {
        return $this->listener->id();
    }

    public function on(Event $event) : bool
    {
        try {
            return $this->listener->on($event);
        } catch (\Throwable $exception) {
            $this->logger->debug('Listener "{listener}" has thrown "{class}" exception with "{message}" message on "{event}" event.', [
                'listener' => get_class($this->listener),
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'event' => get_class($event),
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }

    public function replay(Event\Stream $events) : void
    {
        if (!$this->listener instanceof Event\Replayable) {
            return;
        }

        try {
            $this->listener->replay($events);
        } catch (\Throwable $exception) {
            $this->logger->debug('Listener "{listener}" has thrown "{class}" exception with "{message}" message while replaying events.', [
                'listener' => get_class($this->listener),
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }
}
