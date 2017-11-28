<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain\Message;

use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
trait Listening
{
    public function onMessage(Domain\Message $message) : void
    {
        $reflection = new \ReflectionObject($this);

        foreach ($reflection->getMethods() as $method) {
            if (false === $this->isMessageListeningMethod($method, $message)) {
                continue;
            }

            $this->call($method, $message);
        }
    }

    private function isMessageListeningMethod(\ReflectionMethod $method, Domain\Message $message) : bool
    {
        // method must start with "on"...
        if (\mb_substr($method->getName(), 0, 2) !== 'on') {
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

        $actual = new \ReflectionObject($message);
        $actual = $actual->getName();

        // .. and $message & $parameter have the same type
        if ($expected !== $actual) {
            return false;
        }

        return true;
    }

    private function call(\ReflectionMethod $method, Domain\Message $message) : void
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
