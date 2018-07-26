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
 * UUID v4.
 *
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class UUID4 extends UUID
{
    /**
     * Generates (pseudo-)random UUID.
     *
     * @return UUID4
     */
    final public static function create()
    {
        $uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();

        return new static($uuid);
    }
}
