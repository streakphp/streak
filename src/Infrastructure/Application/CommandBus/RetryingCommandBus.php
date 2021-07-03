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

namespace Streak\Infrastructure\Application\CommandBus;

use Streak\Application;
use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Application\CommandBus\RetryingCommandBusTest
 */
class RetryingCommandBus implements Application\CommandBus
{
    private const DEFAULT_MAX_ATTEMPTS = 10;

    private int $numberOfAttempts = 0;

    public function __construct(private Application\CommandBus $bus, private int $maxAttemptsAllowed = self::DEFAULT_MAX_ATTEMPTS)
    {
    }

    public function numberOfAttempts(): int
    {
        return $this->numberOfAttempts;
    }

    public function maxAttemptsAllowed(): int
    {
        return $this->maxAttemptsAllowed;
    }

    public function dispatch(Domain\Command $command): void
    {
        $this->numberOfAttempts = 0;

        try {
            dispatch:
            $this->numberOfAttempts++;
            $this->bus->dispatch($command);
        } catch (Domain\Exception\ConcurrentWriteDetected $exception) {
            if ($this->numberOfAttempts >= $this->maxAttemptsAllowed) {
                throw $exception;
            }

            goto dispatch;
        }
    }
}
