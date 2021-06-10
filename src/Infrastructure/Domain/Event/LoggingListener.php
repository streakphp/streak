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

namespace Streak\Infrastructure\Domain\Event;

use Psr\Log\LoggerInterface;
use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Listener\State;
use Streak\Domain\Query;
use Streak\Domain\QueryHandler;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Domain\Event\LoggingListenerTest
 */
class LoggingListener implements Event\Listener, Event\Listener\Completable, Listener\Resettable, Listener\Stateful, Event\Filterer, QueryHandler
{
    public function __construct(private Event\Listener $listener, private LoggerInterface $logger)
    {
    }

    public function id(): Domain\Id
    {
        return $this->listenerId();
    }

    public function listenerId(): Listener\Id
    {
        return $this->listener->listenerId();
    }

    public function on(Event\Envelope $event): bool
    {
        try {
            return $this->listener->on($event);
        } catch (\Throwable $exception) {
            $this->logger->debug('Listener "{listener}" has thrown "{class}" exception with "{message}" message on "{event}" event.', [
                'listener' => $this->listener::class,
                'class' => $exception::class,
                'message' => $exception->getMessage(),
                'event' => $event->message()::class,
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }

    public function completed(): bool
    {
        if ($this->listener instanceof Event\Listener\Completable) {
            return $this->listener->completed();
        }

        return false;
    }

    public function reset(): void
    {
        if (!$this->listener instanceof Event\Listener\Resettable) {
            return;
        }

        try {
            $this->listener->reset();
        } catch (\Throwable $exception) {
            $this->logger->debug('Listener "{listener}" has thrown "{class}" exception with "{message}" message while resetting.', [
                'listener' => $this->listener::class,
                'class' => $exception::class,
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }

    public function filter(Event\Stream $stream): Event\Stream
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

        throw new Domain\Exception\QueryNotSupported($query);
    }

    public function toState(State $state): State
    {
        if ($this->listener instanceof Listener\Stateful) {
            return $this->listener->toState($state);
        }

        return $state;
    }

    public function fromState(State $state): void
    {
        if ($this->listener instanceof Listener\Stateful) {
            $this->listener->fromState($state);
        }
    }
}
