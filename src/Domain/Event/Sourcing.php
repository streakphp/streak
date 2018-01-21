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
 */
trait Sourcing // implements Event\Consumer, Event\Producer, Domain\Identifiable
{
    private $events = [];
    private $last;
    private $replaying = false;
    private $lastReplayed;

    final public function replay(Event\Stream $events) : void
    {
        try {
            $this->replaying = true;

            foreach ($events as $event) {
                $this->applyEvent($event);
            }

            $this->replaying = false;
        } catch (Exception\SourcingObjectWithEventFailed $exception) {
            $this->replaying = false;

            throw $exception;
        }
    }

    final public function lastReplayed() : ?Event
    {
        return $this->lastReplayed;
    }

    final public function last() : ?Event
    {
        return $this->last;
    }

    /**
     * @return Event[]
     */
    final public function events() : array
    {
        return $this->events;
    }

    final protected function applyEvent(Event $event) : void
    {
        if (!$this instanceof Event\Consumer) {
            throw new Exception\SourcingObjectWithEventFailed($this, $event);
        }

        $reflection = new \ReflectionObject($this);

        $methods = [];
        foreach ($reflection->getMethods() as $method) {
            // method is not current method...
            if (__FUNCTION__ === $method->getName()) {
                continue;
            }

            // ...and its name must start with "apply"
            if ('apply' !== \mb_substr($method->getName(), 0, 5)) {
                continue;
            }

            // ...and have exactly one parameter...
            if (1 !== $method->getNumberOfParameters()) {
                continue;
            }

            // ...which is required...
            if (1 !== $method->getNumberOfRequiredParameters()) {
                continue;
            }

            $parameter = $method->getParameters()[0];
            $parameter = $parameter->getClass();

            // ..and its an event...
            if (false === $parameter->isSubclassOf(Event::class)) {
                continue;
            }

            $target = new \ReflectionClass($event);

            // .. and $event is type or subtype of defined $parameter
            while ($parameter->getName() !== $target->getName()) {
                $target = $target->getParentClass();

                if (false === $target) {
                    continue 2;
                }
            }

            $methods[] = $method;
        }

        if (0 === \count($methods)) {
            throw new Exception\NoEventApplyingMethodFound($this, $event);
        }

        // TODO: filter methods matching given event exactly and if it wont work, than filter by direct ascendants of given event and so on

        if (\count($methods) > 1) {
            throw new Exception\TooManyEventApplyingMethodsFound($this, $event);
        }

        $method = array_shift($methods);

        $isPublic = $method->isPublic();

        if (false === $isPublic) {
            $method->setAccessible(true);
        }

        $method->invoke($this, $event);

        if (false === $isPublic) {
            $method->setAccessible(false);
        }

        // TODO: test if lastReplayed/last do not change in case of listening error
        $this->last = $event;
        if ($this->replaying) {
            $this->lastReplayed = $event;
        } else {
            $this->events[] = $event;
        }
    }
}
