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
use Streak\Domain\Event\Exception;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
trait Sourcing
{
    private $events = [];
    private $replaying = false;
    private $lastReplayed;

    final public function replay(Domain\Event ...$events) : void
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

    final public function lastReplayed() : Domain\Event
    {
        return $this->lastReplayed;
    }

    /**
     * @return Domain\Event[]
     */
    final public function events() : array
    {
        return $this->events;
    }

    final protected function applyEvent(Domain\Event $event) : void
    {
        if ($this->replaying) {
            $this->lastReplayed = $event;
        } else {
            $this->events[] = $event;
        }

        $reflection = new \ReflectionObject($this);

        $found = [];
        foreach ($reflection->getMethods() as $method) {
            if (false === $this->isMessageListeningMethod($method, $event)) {
                continue;
            }

            $found[] = $method;
        }

        if (\count($found) === 0) {
            throw new Exception\SourcingObjectWithEventFailed($this, $event);
        }

        if (\count($found) > 1) {
            throw new Exception\SourcingObjectWithEventFailed($this, $event);
        }

        $this->call($found[0], $event);
    }

    final private function isMessageListeningMethod(\ReflectionMethod $method, Domain\Event $event) : bool
    {
        // method must start with "apply"...
        if (\mb_substr($method->getName(), 0, 5) !== 'apply') {
            return false;
        }

        // ...and have exactly one parameter...
        if ($method->getNumberOfParameters() !== 1) {
            return false;
        }

        // ...which is required...
        if ($method->getNumberOfRequiredParameters() !== 1) {
            return false;
        }

        $expected = $method->getParameters()[0];
        $expected = $expected->getClass()->getName();

        $actual = new \ReflectionObject($event);
        $actual = $actual->getName();

        // .. and $message & $parameter have the same type
        if ($expected !== $actual) {
            return false;
        }

        return true;
    }

    final private function call(\ReflectionMethod $method, Domain\Message $message) : void
    {
        $isPublic = $method->isPublic();

        if (false === $isPublic) {
            $method->setAccessible(true);
        }

        $method->invoke($this, $message);

        if (false === $isPublic) {
            $method->setAccessible(false);
        }
    }
}
