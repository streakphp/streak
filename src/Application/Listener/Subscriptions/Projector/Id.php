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

namespace Streak\Application\Listener\Subscriptions\Projector;

use Streak\Domain;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class Id implements Event\Listener\Id
{
    public const ID = 'e25e531b-1d6f-4881-b9d4-ad99268a3122';

    public function __construct(string $id = self::ID)
    {
        if ($id !== $this::ID) {
            throw new \InvalidArgumentException('Wrong UUID.');
        }
    }

    public function equals($id) : bool
    {
        if (!$id instanceof self) {
            return false;
        }

        return true;
    }

    public function toString() : string
    {
        return self::ID;
    }

    public static function fromString(string $id) : Domain\Id
    {
        return new self($id);
    }
}
