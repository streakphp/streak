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

use Streak\Domain;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
trait Projecting // implements Application\Projector
{
    use Event\Consuming {
        Event\Consuming::replay as private doReplay;
    }
    use Event\Listening {
        Event\Listening::on as private onEvent;
    }

    final public function replay(Event\Stream $events) : void
    {
        $this->onReplay();
        $this->doReplay($events);
    }

    final public function lastReplayed() : ?Domain\Event
    {
        return $this->lastReplayed;
    }

    /**
     * @throws \Exception
     */
    public function on(Domain\Event $event) : bool
    {
        try {
            $this->preEvent($event);
            $processed = $this->onEvent($event);
            $this->postEvent($event);
        } catch (\Exception $exception) {
            $this->onException($exception);
            throw $exception;
        }

        return $processed;
    }

    abstract protected function onReplay() : void;

    abstract protected function preEvent(Event $event) : void;

    abstract protected function postEvent(Event $event) : void;

    abstract protected function onException(\Exception $exception) : void;
}
