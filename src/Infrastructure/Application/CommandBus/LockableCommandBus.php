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
 * @see \Streak\Infrastructure\Application\CommandBus\LockableCommandBusTest
 */
class LockableCommandBus implements Application\CommandBus
{
    private Application\CommandBus $bus;
    private bool $locked = false;

    public function __construct(Application\CommandBus $bus)
    {
        $this->bus = $bus;
    }

    public function dispatch(Domain\Command $command): void
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
