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
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
abstract class UUID implements Domain\Id
{
    private $value;

    final public function __construct(string $value)
    {
        $value = mb_strtoupper($value);
        $value = trim($value);

        try {
            $uuid = \Ramsey\Uuid\Uuid::fromString($value);
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException('', 0, $e);
        }

        $null = \Ramsey\Uuid\Uuid::fromString('00000000-0000-0000-0000-000000000000');

        if ($uuid->equals($null)) {
            throw new \InvalidArgumentException();
        }

        $this->value = $value;
    }

    final public function equals($uuid) : bool
    {
        if (!$uuid instanceof self) {
            return false;
        }

        if ($uuid->value !== $this->value) {
            return false;
        }

        return true;
    }

    final public function toString() : string
    {
        return $this->value;
    }

    final public static function fromString(string $uuid) : Domain\Id
    {
        return new static($uuid);
    }
}
