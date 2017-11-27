<?php

/*
 * This file is part of the cbs package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Application;

use Streak\Application;
use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @TODO: create generic trait e.g. Message\MessageListening
 * @TODO: create Saga interface as role interface as in @see https://martinfowler.com/bliki/RoleInterface.html
 */
abstract class Saga
{
    private $bus;

    final public function __construct(Application\CommandBus $bus)
    {
        $this->bus = $bus;
    }

    /**
     * @throws Exception\CommandNotSupported
     */
    public function dispatch(Application\Command $command) : void
    {
        $this->bus->dispatch($command);
    }

    abstract public static function startsFor(Domain\Message $message) : bool;

    abstract public static function stopsFor(Domain\Message $message) : bool;

    public function on(Domain\Message $message) : void
    {
        $reflection = new \ReflectionObject($this);

        $found = null;
        foreach ($reflection->getMethods() as $method) {
            // method must start with "on"...
            if (\mb_substr($method->getName(), 0, 2) !== 'on') {
                continue;
            }
            // ...and have exactly one parameter...
            if ($method->getNumberOfParameters() !== 1) {
                continue;
            }
            // ...which is required...
            if ($method->getNumberOfRequiredParameters() !== 1) {
                continue;
            }

            $parameter = $method->getParameters()[0];

            // .. and its type is same as $event
            if ($parameter->getClass()->getName() !== (new \ReflectionObject($message))->getName()) {
                continue;
            }

            $found = $method;
        }

        if (null === $found) {
            return;
        }

        if ($found->isPrivate() || $found->isProtected()) {
            $found->setAccessible(true);
        }

        $found->invoke($this, $message);
    }
}
