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

namespace Streak\Application\Command\ScheduledCommand;

use Streak\Application\Command\ScheduleCommand;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Repository
{
    public function add(ScheduleCommand $command) : void;
}
