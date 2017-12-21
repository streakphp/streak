<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Streak\Domain\Message;

use Streak\Domain;
use Streak\Domain\Message;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
trait Listening // implements Message\Listener
{
    public function on(Domain\Message $message) : void
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
            if ('on' !== \mb_substr($method->getName(), 0, 2)) {
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

            // ..and its a message...
            if (false === $parameter->isSubclassOf(Domain\Message::class)) {
                continue;
            }

            $target = new \ReflectionClass($message);

            // .. and $message is type or subtype of defined $parameter
            while ($parameter->getName() !== $target->getName()) {
                $target = $target->getParentClass();

                if (false === $target) {
                    continue 2;
                }
            }

            $method->invoke($this, $message);

            return;
        }
    }
}
