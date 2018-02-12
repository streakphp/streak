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

use Streak\Domain;

/**
 * UUID v4.
 *
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see https://gist.github.com/tdomarkas/c5fbc10385ae004cbde6
 */
class UUID implements Domain\Id
{
    private $value;

    final public function __construct(string $value)
    {
        $value = mb_strtoupper($value);
        $value = trim($value);

        if (!uuid_is_valid($value)) {
            throw new \InvalidArgumentException();
        }

        if (uuid_is_null($value)) {
            throw new \InvalidArgumentException();
        }

        $this->value = $value;
    }

    public static function create()
    {
        $uuid = uuid_create(UUID_TYPE_RANDOM);

        return new static($uuid);
    }

    public function equals($uuid) : bool
    {
        if (!$uuid instanceof self) {
            return false;
        }

        if (0 !== uuid_compare($uuid->value, $this->value)) {
            return false;
        }

        return true;
    }

    public function toString() : string
    {
        return $this->value;
    }

    public static function fromString(string $uuid) : Domain\Id
    {
        return new self($uuid);
    }
}
