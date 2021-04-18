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

namespace Streak\Domain\Event;

use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * TODO: remove
 */
trait Consuming // implements Event\Replayable
{
    private $replaying = false;
    private $lastReplayed;

    abstract public function on(Event\Envelope $event): bool;

    final public function replay(Event\Stream $events): void
    {
        foreach ($events as $event) {
            $this->on($event);
            $this->lastReplayed = $event;
        }
    }

    final public function lastReplayed(): ?Event\Envelope
    {
        return $this->lastReplayed;
    }
}
