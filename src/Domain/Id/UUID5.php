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

namespace Streak\Domain\Id;

/**
 * UUID v5.
 *
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
abstract class UUID5 extends UUID
{
    /**
     * Generates deterministic UUID based on SHA1 of static namespace (also UUID) and name.
     *
     * @param string $name
     *
     * @return static
     */
    final public static function create(string $name)
    {
        $uuid = \Ramsey\Uuid\Uuid::uuid5(self::namespace()->toString(), $name)->toString();

        return new static($uuid);
    }

    /**
     * Namespace UUID required for UUID v5 generation.
     *
     * @return UUID
     */
    abstract protected static function namespace() : UUID;
}
