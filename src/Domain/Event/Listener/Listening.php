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

use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
trait Listening
{
    public function on(Domain\Event $event) : bool
    {
        try {
            $reflection = new \ReflectionObject($this);
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

                // ...and its name must start with "on"
                if ('on' !== \mb_substr($method->getName(), 0, 2)) {
                    continue;
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
                $parameter = $parameter->getClass();
                if (false === $parameter->isSubclassOf(Domain\Event::class)) {
                    continue;
                }

                // .. and $event is type or subtype of $parameter
                $target = new \ReflectionClass($event);
                while ($parameter->getName() !== $target->getName()) {
                    $target = $target->getParentClass();

                    if (false === $target) {
                        continue 2;
                    }
                }

                $this->preEvent($event);
                $method->invoke($this, $event);
                $this->postEvent($event);

                $routed = true;

                break; // only once
            }
        } catch (\Throwable $exception) {
            $this->onException($exception);

            throw $exception;
        }

        return $routed;
    }

    /**
     * @codeCoverageIgnore
     */
    private function preEvent(Domain\Event $event) : void
    {
    }

    /**
     * @codeCoverageIgnore
     */
    private function postEvent(Domain\Event $event) : void
    {
    }

    /**
     * @codeCoverageIgnore
     */
    private function onException(\Throwable $exception) : void
    {
    }
}
