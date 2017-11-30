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
        if (!$this->aggregateRootId()->equals($event->aggregateRootId())) {
            throw new Domain\Exception\EventAndConsumerMismatch($this, $event);
        }

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

        // ... but it cant be our above-implemented applyEvent() method
        if ($method->getName() === 'applyEvent') {
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
            $name1 = $actual->getName();
            $name2 = $expected->getName();
            if ($actual->getName() === $expected->getName()) {
                return true;
            }
        } while ($actual = $actual->getParentClass());


        return false;
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
