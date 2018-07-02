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
 * UUID v3.
 *
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
abstract class UUID3 extends UUID
{
    /**
     * Generates deterministic UUID based on MD5 of static namespace (also UUID) and name.
     *
     * Use it only if backward compatibility with e.g. outside system is required, use v5 otherwise.
     *
     * @param string $name
     *
     * @return static
     */
    final public static function create(string $name)
    {
        $uuid = \Ramsey\Uuid\Uuid::uuid3(self::namespace()->toString(), $name)->toString();

        return new static($uuid);
    }

    /**
     * Namespace UUID required for UUID v5 generation.
     *
     * @return UUID
     */
    abstract protected static function namespace() : UUID;
}
