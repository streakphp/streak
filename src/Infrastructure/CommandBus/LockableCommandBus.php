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

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\CommandBus\LockableCommandBusTest
 */
class LockableCommandBus implements CommandBus
{
    private CommandBus $bus;
    private bool $locked = false;

    public function __construct(CommandBus $bus)
    {
        $this->bus = $bus;
    }

    public function register(CommandHandler $handler): void
    {
        $this->bus->register($handler);
    }

    public function dispatch(Command $command): void
    {
        if (true === $this->locked) {
            return;
        }

        $this->bus->dispatch($command);
    }

    public function lock(): void
    {
        $this->locked = true;
    }

    public function unlock(): void
    {
        $this->locked = false;
    }
}
