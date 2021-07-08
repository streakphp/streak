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

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class ScheduleCommand implements Command
{
    private $command;

    private $when;

    public function __construct(Command $command, \DateTimeInterface $when)
    {
        $this->command = $command;
        $this->when = $when;
    }

    public function command() : Command
    {
        return $this->command;
    }

    public function when() : \DateTimeInterface
    {
        return $this->when;
    }
}
