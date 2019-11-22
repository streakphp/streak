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

namespace Streak\Application\Sensor;

use Streak\Domain\Event;
use Streak\Domain\Id;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
trait Processing
{
    private $pending = [];
    private $events = [];
    private $last;

    abstract public function producerId() : Id;

    final public function last() : ?Event\Envelope
    {
        return $this->last;
    }

    /**
     * @return Event\Envelope[]
     */
    final public function events() : array
    {
        return $this->events;
    }

    /**
     * @throws \Throwable
     */
    final public function process(...$messages) : void
    {
        $this->pending = [];

        try {
            $reflection = new \ReflectionObject($this);
            foreach ($messages as $message) {
                $routed = false;
                foreach ($reflection->getMethods() as $method) {
                    // method is not current method...
                    if (__FUNCTION__ === $method->getName()) {
                        continue;
                    }

                    // ...is public...
                    if (!$method->isPublic()) {
                        continue;
                    }

                    // ...and its name must start with "process"
                    if ('process' !== \mb_substr($method->getName(), 0, 7)) {
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

                    // ...and do not allow nulls...
                    if (true === $parameter->allowsNull()) {
                        continue;
                    }

                    $parameterType = $parameter->getType();
                    $parameterType = $parameterType->getName();

                    $messageIsClass = class_exists($parameterType);
                    $parameterIsClass = is_object($message);

                    if (true === $messageIsClass && true === $parameterIsClass) {
                        $parameter = $parameter->getClass();
                        $target = new \ReflectionClass($message);

                        // .. and $message is type or subtype of defined $parameter
                        while ($parameter->getName() !== $target->getName()) {
                            $target = $target->getParentClass();

                            if (false === $target) {
                                continue 2;
                            }
                        }

                        goto route;
                    }

                    $messageType = gettype($message);

                    if ('boolean' === $messageType) {
                        $messageType = 'bool';
                    }

                    if ('integer' === $messageType) {
                        $messageType = 'int';
                    }

                    if ($messageType === $parameterType) {
                        goto route;
                    }

                    continue;
                    route:

                    if (true === $routed) {
                        throw new \BadMethodCallException('Too many processing functions found.');
                    }

                    $method->invoke($this, $message);

                    $routed = true;
                }

                if (false === $routed) {
                    throw new \InvalidArgumentException();
                }
            }
        } catch (\Throwable $e) {
            $this->pending = [];
            throw $e;
        }

        $this->events = array_merge($this->events, $this->pending);
        $this->last = end($this->events);
        $this->pending = [];
    }

    final private function addEvent(Event $event) : void
    {
        $this->pending[] = Event\Envelope::new($event, $this->producerId());
    }
}
