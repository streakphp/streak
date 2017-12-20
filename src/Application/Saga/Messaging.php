<?php

/*
 * This file is part of the cbs package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Application\Saga;

use Streak\Application;
use Streak\Application\Saga\Exception;
use Streak\Domain;
use Streak\Infrastructure\CommandBus\NullCommandBus;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
trait Messaging
{
    abstract public function beginsWith(Domain\Message $message) : bool;

    public function replay(Domain\Message ...$messages) : void
    {
        if (0 === count($messages)) {
            return;
        }

        $first = array_shift($messages);

        if (!$this->beginsWith($first)) {
            throw new Exception\InvalidFirstMessageGiven($first);
        }

        $this->on($first, new NullCommandBus());

        foreach ($messages as $message) {
            if (!$this->beginsWith($message)) { // we do not handle messages that are starting command - very naive implementation
                $this->on($message, new NullCommandBus());
            }
        }
    }

    public function on(Domain\Message $message, Application\CommandBus $bus) : void
    {
        $reflection = new \ReflectionObject($this);

        foreach ($reflection->getMethods() as $method) {
            // method is not current method...
            if ($method->getName() === __FUNCTION__) {
                continue;
            }

            // ...is public...
            if (!$method->isPublic()) {
                continue;
            }

            // ...and its name must start with "on"
            if (\mb_substr($method->getName(), 0, 2) !== 'on') {
                continue;
            }

            // ...and have exactly 2 parameters...
            if ($method->getNumberOfParameters() !== 2) {
                continue;
            }

            $first = $method->getParameters()[0];
            $second = $method->getParameters()[1];

            // ...and first parameter is required
            if ($first->allowsNull()) {
                continue;
            }

            // ..and second parameter is required...
            if ($second->allowsNull()) {
                continue;
            }

            // ..and first parameter is a message...
            $first = $first->getClass();
            if (false === $first->isSubclassOf(Domain\Message::class)) {
                continue;
            }
            // .. and $message is type or subtype of defined $parameter
            $target = new \ReflectionClass($message);
            while($first->getName() !== $target->getName()) {
                $target = $target->getParentClass();

                if (false === $target) {
                    continue 2;
                }
            }

            // ...and second parameter is a command bus...
            $second = $second->getClass();
            if (false === $second->implementsInterface(Application\CommandBus::class)) {
                continue;
            }

            $method->invoke($this, $message, $bus);

            return;
        }
    }
}
