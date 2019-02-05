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
class Uuid implements Domain\Id
{
    private $value;

    final public function __construct(string $value)
    {
        $value = mb_strtolower($value);
        $value = trim($value);

        $this->value = $value;
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

    /**
     * @param string $uuid
     *
     * @return Domain\Id|Uuid|static
     */
    public static function fromString(string $uuid) : Domain\Id
    {
        return new static($uuid);
    }

    /**
     * @param Uuid $uuid
     *
     * @return static
     */
    final public static function fromUuid(Uuid $uuid) : self
    {
        return new static($uuid->toString());
    }
}
