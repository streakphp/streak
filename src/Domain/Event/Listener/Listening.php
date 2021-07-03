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

namespace Streak\Domain\Event\Listener;

use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Domain\Event\Listener\ListeningTest
 */
trait Listening
{
    public function on(Event\Envelope $event): bool
    {
        $reflection = new \ReflectionObject($this);

        foreach ($reflection->getMethods() as $method) {
            // method is not current method...
            if (__FUNCTION__ === $method->getName()) {
                continue;
            }

            // ...is public...
            if (!$method->isPublic()) {
                continue;
            }

            // ...and its name must start with "on"
            if ('on' !== mb_substr($method->getName(), 0, 2)) {
                continue;
            }

            // .. and if it has return type it must be boolean or void...
            if ($method->hasReturnType()) {
                $type = $method->getReturnType();

                if (!$type instanceof \ReflectionNamedType) { // union type
                    continue;
                }

                if (!\in_array($type->getName(), ['bool', 'void'])) {
                    continue;
                }
            }

            // ...and have exactly one parameter...
            if (1 !== $method->getNumberOfParameters()) {
                continue;
            }

            // ...and it is required
            $parameter = $method->getParameters()[0];
            if ($parameter->allowsNull()) {
                continue;
            }

            // ..and it is an event...
            $parameter = $parameter->getType();

            if (!$parameter instanceof \ReflectionNamedType) { // union type
                continue;
            }

            $parameter = new \ReflectionClass($parameter->getName());

            if (false === $parameter->isSubclassOf(Event::class)) {
                continue;
            }

            // .. and $event is type or subtype of $parameter
            $target = new \ReflectionClass($event->message());
            while ($parameter->getName() !== $target->getName()) {
                $target = $target->getParentClass();

                if (false === $target) {
                    continue 2;
                }
            }

            try {
                $this->preEvent($event->message());
                $listenedTo = $method->invoke($this, $event->message());
                $this->postEvent($event->message());
            } catch (\Throwable $exception) {
                $this->onException($exception);

                throw $exception;
            }

            if (null === $listenedTo) {
                return true; // by default, if no confirmation is given, we assume that $event was listened (and processed) by $this listener.
            }

            if (false === \is_bool($listenedTo)) {
                throw new \UnexpectedValueException(sprintf('Value returned by %s::%s($event) expected to be null or boolean, but %s was given.', $reflection->getShortName(), $method->getName(), \gettype($listenedTo)));
            }

            return $listenedTo;
        }

        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    private function preEvent(Event $event): void
    {
    }

    /**
     * @codeCoverageIgnore
     */
    private function postEvent(Event $event): void
    {
    }

    /**
     * @codeCoverageIgnore
     */
    private function onException(\Throwable $exception): void
    {
    }
}
