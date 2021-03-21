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
 * @see \Streak\Infrastructure\CommandBus\NullCommandBusTest
 */
class NullCommandBus implements CommandBus
{
    public function register(CommandHandler $handler) : void
    {
    }

    public function dispatch(Command $command) : void
    {
    }
}
