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

namespace Streak\Infrastructure\Application\Listener\Subscriber;

use Streak\Domain\Event\Listener;

/**
 * @see \Printify\Tests\Invoices\Application\Projectors\InvoicesList\Projector\IdTest
 */
final class Id implements Listener\Id
{
    private const ID = '00000000-0000-0000-0000-000000000000';

    public function equals(object $id): bool
    {
        if (!$id instanceof self) {
            return false;
        }

        return true;
    }

    public function toString(): string
    {
        return self::ID;
    }

    public static function fromString(string $id): self
    {
        return new self();
    }
}
