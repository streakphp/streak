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
use Streak\Application\Exception;
use Streak\Application\Query;
use Streak\Application\QueryHandler;
use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class LoggingListener implements Event\Listener, Event\Listener\Replayable, Event\Listener\Completable, Listener\Resettable, Event\Filterer, QueryHandler
{
    private $listener;
    private $logger;

    public function __construct(Event\Listener $listener, Log\LoggerInterface $logger)
    {
        $this->listener = $listener;
        $this->logger = $logger;
    }

    public function id() : Domain\Id
    {
        return $this->listenerId();
    }

    public function listenerId() : Listener\Id
    {
        return $this->listener->listenerId();
    }

    public function on(Event\Envelope $event) : bool
    {
        try {
            return $this->listener->on($event);
        } catch (\Throwable $exception) {
            $this->logger->debug('Listener "{listener}" has thrown "{class}" exception with "{message}" message on "{event}" event.', [
                'listener' => get_class($this->listener),
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'event' => get_class($event->message()),
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }

    public function replay(Event\Stream $events) : void
    {
        if (!$this->listener instanceof Event\Listener\Replayable) {
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

    public function completed() : bool
    {
        if ($this->listener instanceof Event\Listener\Completable) {
            return $this->listener->completed();
        }

        return false;
    }

    public function reset() : void
    {
        if (!$this->listener instanceof Event\Listener\Resettable) {
            return;
        }

        try {
            $this->listener->reset();
        } catch (\Throwable $exception) {
            $this->logger->debug('Listener "{listener}" has thrown "{class}" exception with "{message}" message while resetting.', [
                'listener' => get_class($this->listener),
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }

    public function filter(Event\Stream $stream) : Event\Stream
    {
        if (!$this->listener instanceof Event\Filterer) {
            return $stream;
        }

        return $this->listener->filter($stream);
    }

    public function handleQuery(Query $query)
    {
        if ($this->listener instanceof QueryHandler) {
            return $this->listener->handleQuery($query);
        }

        throw new Exception\QueryNotSupported($query);
    }
}
