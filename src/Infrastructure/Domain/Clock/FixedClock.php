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

namespace Streak\Infrastructure\Domain\Clock;

use Streak\Domain\Clock;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Domain\Clock\FixedClockTest
 */
class FixedClock implements Clock
{
    private const DATE_FORMAT = 'U.u';

    private \DateTimeImmutable $now;

    public function __construct(\DateTimeInterface $time)
    {
        $this->now = \DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $time->format(self::DATE_FORMAT));
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }
}
