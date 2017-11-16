<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Domain\EventSourced;

use Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Replayable
{
    public function replayEvents(Domain\Event ...$events) : void;

    public function lastReplayedEvent() : Domain\Event;
}
