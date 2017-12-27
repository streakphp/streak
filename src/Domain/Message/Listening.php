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

namespace Streak\Domain\Message;

use Streak\Domain;

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

            // ...and it is required
            $parameter = $method->getParameters()[0];
            if ($parameter->allowsNull()) {
                continue;
            }

            // ..and it is a message...
            $parameter = $parameter->getClass();
            if (false === $parameter->isSubclassOf(Domain\Message::class)) {
                continue;
            }

            // .. and $message is type or subtype of $parameter
            $target = new \ReflectionClass($message);
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
