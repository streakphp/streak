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
trait Sourcing //implements Event\Consumer, Event\Producer, Domain\Identifiable, Domain\Versionable
{
    /**
     * @var Event\Envelope[]
     */
    private $events = [];
    /**
     * @var Event\Envelope
     */
    private $lastEvent;
    private $replaying = false;
    private $lastReplayed;
    private $version = 0;

    abstract public function producerId() : Domain\Id;

    /**
     * @throws \Throwable
     */
    final public function replay(Event\Stream $stream) : void
    {
        try {
            $this->replaying = true;

            foreach ($stream as $event) {
                $this->applyEvent($event);
            }

            $this->replaying = false;
        } catch (Exception\SourcingObjectWithEventFailed $exception) {
            $this->replaying = false;

            throw $exception;
        }
    }

    final public function lastReplayed() : ?Event\Envelope
    {
        return $this->lastReplayed;
    }

    final public function lastEvent() : ?Event\Envelope
    {
        return $this->lastEvent;
    }

    final public function version() : int
    {
        return $this->version;
    }

    /**
     * @return Event\Envelope[]
     */
    final public function events() : array
    {
        return $this->events;
    }

    public function commit() : void
    {
        $this->version = $this->version + count($this->events);
        $this->events = [];
    }

    /**
     * @throws Event\Exception\NoEventApplyingMethodFound
     * @throws Event\Exception\TooManyEventApplyingMethodsFound
     * @throws \Throwable
     */
    final private function apply(Event $event) : void
    {
        $event = Event\Envelope::new(
            $event,
            $this->producerId(),
            $this->version + count($this->events) + 1 // current version + number of not committed events + 1
        );

        $this->applyEvent($event);
    }

    /**
     * @throws Event\Exception\NoEventApplyingMethodFound
     * @throws Event\Exception\TooManyEventApplyingMethodsFound
     * @throws \Throwable
     */
    final private function applyEvent(Event\Envelope $event) : void
    {
        if (!$this instanceof Event\Consumer) {
            throw new Exception\SourcingObjectWithEventFailed($this, $event);
        }

        if (!$this->producerId()->equals($event->producerId())) {
            throw new Domain\Exception\EventAndConsumerMismatch($this, $event);
        }

        try {
            $version = $this->version; // backup
            $last = $this->lastEvent; // backup
            $lastReplayed = $this->lastReplayed; // backup

            if ($this->replaying) {
                $this->lastEvent = $event;
                $this->version = $event->version();
                $this->lastReplayed = $event;
            } else {
                $this->lastEvent = $event;
                $this->events[] = $event;
            }

            $this->doApplyEvent($event);
        } catch (\Throwable $e) {
            // rollback changes
            if ($this->replaying) {
                $this->lastEvent = $last;
                $this->version = $version;
                $this->lastReplayed = $lastReplayed;
            } else {
                $this->lastEvent = $last;
                array_pop($this->events);
            }

            throw $e;
        }
    }

    /**
     * @throws Event\Exception\NoEventApplyingMethodFound
     * @throws Event\Exception\TooManyEventApplyingMethodsFound
     * @throws \Throwable
     */
    private function doApplyEvent(Event\Envelope $event) : void
    {
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

            // ..and its has class...
            if (null === $parameter) {
                continue;
            }

            // ..and its an event...
            if (false === $parameter->isSubclassOf(Event::class)) {
                continue;
            }

            $target = new \ReflectionClass($event->message());

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

        try {
            $method->invoke($this, $event->message());
        } finally {
            if (false === $isPublic) {
                $method->setAccessible(false);
            }
        }
    }
}
