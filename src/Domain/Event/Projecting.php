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
use Streak\Domain\Message;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
trait Projecting // implements Application\Projector
{
    use Event\Consuming {
        Event\Consuming::replay as private doReplay;
    }
    use Message\Listening {
        Message\Listening::on as private onMessage;
    }

    final public function replay(Domain\Event ...$events) : void
    {
        $this->onReplay();
        $this->doReplay(...$events);
    }

    final public function lastReplayed() : ?Domain\Event
    {
        return $this->lastReplayed;
    }

    /**
     * @throws \Exception
     */
    public function on(Domain\Message $event) : void
    {
        if (!$event instanceof Domain\Event) {
            throw new \InvalidArgumentException('Event expected but message given.');
        }

        try {
            $this->preEvent($event);
            $this->onMessage($event);
            $this->postEvent($event);
        } catch (\Exception $exception) {
            $this->onException($exception);
            throw $exception;
        }
    }

    abstract protected function onReplay() : void;

    abstract protected function preEvent(Event $event) : void;

    abstract protected function postEvent(Event $event) : void;

    abstract protected function onException(\Exception $exception) : void;
}
