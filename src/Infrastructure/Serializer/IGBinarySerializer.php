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

namespace Streak\Infrastructure\Serializer;

use Streak\Infrastructure\Serializer;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class IGBinarySerializer implements Serializer
{
    public function serialize($subject) : string
    {
        return \igbinary_serialize($subject);
    }

    public function unserialize($serialized)
    {
        return \igbinary_unserialize($serialized);
    }
}
