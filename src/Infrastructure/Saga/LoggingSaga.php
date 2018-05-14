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

namespace Streak\Infrastructure\Saga;

use Psr\Log;
use Streak\Application;
use Streak\Domain;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class LoggingSaga implements Application\Saga
{
    private $saga;
    private $logger;

    public function __construct(Application\Saga $saga, Log\LoggerInterface $logger)
    {
        $this->saga = $saga;
        $this->logger = $logger;
    }

    public function completed() : bool
    {
        return $this->saga->completed();
    }

    public function id() : Domain\Id
    {
        return $this->saga->id();
    }

    public function on(Event $event) : bool
    {
        try {
            return $this->saga->on($event);
        } catch (\Throwable $exception) {
            $this->logger->debug('Saga "{saga}" has thrown "{class}" exception with "{message}" message on "{event}" event.', [
                'saga' => get_class($this->saga),
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
        try {
            $this->saga->replay($events);
        } catch (\Throwable $exception) {
            $this->logger->debug('Saga "{saga}" has thrown "{class}" exception with "{message}" message while replaying events.', [
                'saga' => get_class($this->saga),
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }

    public function sagaId() : Application\Saga\Id
    {
        return $this->saga->sagaId();
    }
}
