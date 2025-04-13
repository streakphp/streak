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
 * @see \Streak\Domain\Event\Listener\FilteringTest
 */
trait Filtering
{
    public function filter(Event\Stream $stream): Event\Stream
    {
        $reflection = new \ReflectionObject($this);
        $types = [];

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

            // ...and have exactly one parameter...
            if (1 !== $method->getNumberOfParameters()) {
                continue;
            }

            // ...that is not optional...
            $parameter = $method->getParameters()[0];
            if ($parameter->allowsNull()) {
                continue;
            }

            // ..and it is an event...
            $parameter = $parameter->getType();

            if (!$parameter instanceof \ReflectionNamedType) {
                continue;
            }

            $parameter = new \ReflectionClass($parameter->getName());

            if (false === $parameter->isSubclassOf(Event::class)) {
                continue;
            }

            // ...that is final...
            if (false === $parameter->isFinal()) {
                throw new \InvalidArgumentException(\sprintf('Event class "%s" must be final in order to be used for stream filtering.', $parameter->getName()));
            }

            $types[] = $parameter->getName();
        }

        return $stream->only(...$types);
    }
}
