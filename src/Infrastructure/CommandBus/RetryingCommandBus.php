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

namespace Streak\Infrastructure\CommandBus;

use Streak\Application\Command;
use Streak\Application\CommandBus;
use Streak\Application\CommandHandler;
use Streak\Domain\Exception;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\CommandBus\RetryingCommandBusTest
 */
class RetryingCommandBus implements CommandBus
{
    private CommandBus $bus;
    private int $numberOfAttempts = 0;
    private int $maxAttemptsAllowed = 10;

    public function __construct(CommandBus $bus, int $maxAttemptsAllowed)
    {
        $this->bus = $bus;
        $this->maxAttemptsAllowed = $maxAttemptsAllowed;
    }

    public function numberOfAttempts() : int
    {
        return $this->numberOfAttempts;
    }

    public function maxAttemptsAllowed() : int
    {
        return $this->maxAttemptsAllowed;
    }

    public function register(CommandHandler $handler) : void
    {
        $this->bus->register($handler);
    }

    public function dispatch(Command $command) : void
    {
        $this->numberOfAttempts = 0;
        try {
            dispatch:
                $this->numberOfAttempts++;
            $this->bus->dispatch($command);
        } catch (Exception\ConcurrentWriteDetected $exception) {
            if ($this->numberOfAttempts >= $this->maxAttemptsAllowed) {
                throw $exception;
            }
            goto dispatch;
        }
    }
}
