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
 * @see \Streak\Domain\Id\UUIDTest
 */
class UUID implements Domain\Id
{
    private string $value;

    final public function __construct(string $value)
    {
        $value = mb_strtolower($value);
        $value = trim($value);

        try {
            $uuid = \Ramsey\Uuid\Uuid::fromString($value);
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException(sprintf('Given value "%s" is not an uuid.', $value), 0, $e);
        }

        $null = \Ramsey\Uuid\Uuid::fromString('00000000-0000-0000-0000-000000000000');

        if ($uuid->equals($null)) {
            throw new \InvalidArgumentException();
        }

        $this->value = $value;
    }

    public static function random()
    {
        $uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();

        return new static($uuid);
    }

    public function equals($uuid) : bool
    {
        if (!$uuid instanceof static) {
            return false;
        }

        if ($uuid->value !== $this->value) {
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
        return new static($uuid);
    }
}
