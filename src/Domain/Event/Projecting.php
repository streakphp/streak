<?php

/*
 * This file is part of the cbs package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Streak\Domain\Event;

use Streak\Domain;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
trait Projecting // implements Application\Projector
{
    use Event\Consuming {
        replay as private;
        replay as doReplay;
    }
    use Event\Listening;

    abstract public function onReplay() : void;

    final public function replay(Domain\Event ...$events) : void
    {
        $this->onReplay();
        $this->doReplay(...$events);
    }

    final public function lastReplayed() : ?Domain\Event
    {
        return $this->lastReplayed;
    }
}
