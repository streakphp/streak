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

namespace Streak\Domain\Command;

use Streak\Domain\AggregateRoot;
use Streak\Domain\Command;
use Streak\Domain\Exception\CommandNotSupported;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Domain\Command\HandlingTest
 */
trait Handling
{
    public function handleCommand(Command $command): void
    {
        if ($command instanceof AggregateRootCommand && $this instanceof AggregateRoot) {
            if (false === $this->aggregateRootId()->equals($command->aggregateRootId())) {
                throw new CommandNotSupported($command);
            }
        }

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

            // ...and its name must start with "handle"...
            if ('handle' !== mb_substr($method->getName(), 0, 6)) {
                continue;
            }

            // .. and if it has return type it must be void...
            if ($method->hasReturnType()) {
                $type = $method->getReturnType();

                if (!$type instanceof \ReflectionNamedType) {
                    continue;
                }

                if ('void' !== $type->getName()) {
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

            // ..and it is a command...
            $parameter = $parameter->getType();

            if (!$parameter instanceof \ReflectionNamedType) {
                continue;
            }

            $parameter = new \ReflectionClass($parameter->getName());

            if (false === $parameter->isSubclassOf(Command::class)) {
                continue;
            }

            // .. and $query is type or subtype of $parameter
            $target = new \ReflectionClass($command);
            while ($parameter->getName() !== $target->getName()) {
                $target = $target->getParentClass();

                if (false === $target) {
                    continue 2;
                }
            }

            $method->invoke($this, $command);

            return;
        }

        throw new CommandNotSupported($command);
    }
}
