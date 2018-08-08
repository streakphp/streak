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
use Zumba\JsonSerializer\JsonSerializer as ZumbaJsonSerializer;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class JsonSerializer implements Serializer
{
    private $serializer;

    public function __construct()
    {
        $this->serializer = new ZumbaJsonSerializer();
    }

    public function serialize($subject) : string
    {
        return $this->serializer->serialize($subject);
    }

    public function unserialize($serialized)
    {
        return $this->serializer->unserialize($serialized);
    }
}
