<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain\Event;

use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
trait Consuming
{
    private $replaying = false;
    private $lastReplayed;

    abstract public function onEvent(Domain\Event $event) : void;

    final public function replay(Domain\Event ...$events) : void
    {
        foreach ($events as $event) {
            $this->onEvent($event);
            $this->lastReplayed = $event;
        }
    }

    final public function lastReplayed() : ?Domain\Event
    {
        return $this->lastReplayed;
    }
}
