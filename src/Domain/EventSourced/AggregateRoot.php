<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain\EventSourced;

use Streak\Domain;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
abstract class AggregateRoot extends Domain\AggregateRoot implements Replayable
{
    /**
     * @var Event[]
     */
    private $events = [];

    /**
     * @var Event
     */
    private $lastReplayedEvent;

    final public function replayEvents(Event ...$events) : void
    {
        foreach ($events as $event) {
            $this->replayEvent($event);
        }
    }

    private function replayEvent(Event $event) : void
    {
        $this->invokeApplyMethod($event);
        $this->lastReplayedEventIs($event);
    }

    private function invokeApplyMethod(Event $event) : void
    {
        $reflection = new \ReflectionObject($this);

        $found = null;
        foreach ($reflection->getMethods() as $method) {
            // method must start with "apply"...
            if (mb_substr($method->getName(), 0, 5) !== 'apply') {
                continue;
            }
            // ...and end with "Event"...
            if (mb_substr($method->getName(), 0, -5) !== 'Event') {
                continue;
            }
            // ...and have exactly one parameter...
            if ($method->getNumberOfParameters() !== 1) {
                continue;
            }
            // ...which is required...
            if ($method->getNumberOfRequiredParameters() !== 1) {
                continue;
            }

            $parameter = $method->getParameters()[0];

            // .. and its type is same as $event
            if ($parameter->getClass()->getName() !== (new \ReflectionObject($event))->getName()) {
                continue;
            }

            if ($found !== null) {
                throw new Exception\MoreThanOneEventApplyingMethodFound($this, $event);
            }

            $found = $method;
        }

        if ($found === null) {
            throw new Exception\EventApplyingMethodNotFound($this, $event);
        }

        $found->invoke($this, $event);
    }

    private function lastReplayedEventIs(Event $event) : void
    {
        $this->lastReplayedEvent = $event;
    }

    final public function lastReplayedEvent() : Event
    {
        return $this->lastReplayedEvent;
    }

    final protected function applyEvent(Event $event) : void
    {
        $this->invokeApplyMethod($event);
        $this->addEvent($event);
    }

    /**
     * @param Event $event
     */
    private function addEvent(Event $event) : void
    {
        $this->events[] = $event;
    }

    /**
     * @return Event[]
     */
    final public function events() : array
    {
        return $this->events;
    }
}
