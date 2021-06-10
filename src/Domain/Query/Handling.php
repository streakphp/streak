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

namespace Streak\Domain\Query;

use Streak\Domain\Exception\QueryNotSupported;
use Streak\Domain\Query;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Domain\Query\HandlingTest
 */
trait Handling
{
    public function handleQuery(Query $query)
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

            // ...and its name must start with "handle"
            if ('handle' !== mb_substr($method->getName(), 0, 6)) {
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

            // ..and it is a query...
            $parameter = $parameter->getType();

            if (!$parameter instanceof \ReflectionNamedType) {
                continue;
            }

            $parameter = new \ReflectionClass($parameter->getName());

            if (false === $parameter->isSubclassOf(Query::class)) {
                continue;
            }

            // .. and $query is type or subtype of $parameter
            $target = new \ReflectionClass($query);
            while ($parameter->getName() !== $target->getName()) {
                $target = $target->getParentClass();

                if (false === $target) {
                    continue 2;
                }
            }

            return $method->invoke($this, $query);
        }

        throw new QueryNotSupported($query);
    }
}
