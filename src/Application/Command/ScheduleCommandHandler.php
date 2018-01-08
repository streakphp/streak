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

namespace Streak\Application\Command;

use Streak\Application\Command;
use Streak\Application\CommandHandler;
use Streak\Application\Exception;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class ScheduleCommandHandler implements CommandHandler
{
    private $commands;

    public function __construct(ScheduledCommand\Repository $commands)
    {
        $this->commands = $commands;
    }

    public function handle(Command $command) : void
    {
        if (!$command instanceof ScheduleCommand) {
            throw new Exception\CommandNotSupported($command);
        }

        $this->commands->add($command);
    }
}
