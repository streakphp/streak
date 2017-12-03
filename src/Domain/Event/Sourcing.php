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
use Streak\Domain\AggregateRoot;
use Streak\Domain\Event;
use Streak\Domain\Event\Exception;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
trait Sourcing //implements Consumer, Source
{
    private $events = [];
    private $replaying = false;
    private $lastReplayed;

    abstract public function aggregateRootId() : AggregateRoot\Id;

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

    final public function lastReplayed() : ?Domain\Event
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
        if (!$this instanceof Event\Consumer) {
            throw new Exception\SourcingObjectWithEventFailed($this, $event);
        }

        if (!$this->aggregateRootId()->equals($event->aggregateRootId())) {
            throw new Domain\Exception\EventAndConsumerMismatch($this, $event);
        }

        if ($this->replaying) {
            $this->lastReplayed = $event;
        } else {
            $this->events[] = $event;
        }

        $reflection = new \ReflectionObject($this);

        $methods = [];
        foreach ($reflection->getMethods() as $method) {
            if (false === $this->isEventListeningMethod($method, $event)) {
                continue;
            }

            $methods[] = $method;
        }

        if (\count($methods) === 0) {
            throw new Exception\NoEventApplyingMethodFound($this, $event);
        }

        // TODO: filter methods matching given event exactly and if it wont work, than filter by direct ascendants of given event and so on

        if (\count($methods) > 1) {
            throw new Exception\TooManyEventApplyingMethodsFound($this, $event);
        }

        $this->call($methods[0], $event);
    }

    final private function isEventListeningMethod(\ReflectionMethod $method, Domain\Event $event) : bool
    {
        if (false === $this->isProperMethodName($method->getName())) {
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
        $expected = $expected->getClass();

        $actual = new \ReflectionClass($event);

        // .. and $event is type or subtype of defined $parameter
        do {
            if ($actual->getName() === $expected->getName()) {
                return true;
            }
        } while ($actual = $actual->getParentClass());


        return false;
    }

    final private function isProperMethodName(string $name) : bool
    {
        if ($name === 'applyEvent') {
            return false;
        }

        if (\mb_substr($name, 0, 5) !== 'apply') {
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
