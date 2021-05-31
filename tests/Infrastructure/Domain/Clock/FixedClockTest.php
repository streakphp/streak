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

use PHPUnit\Framework\TestCase;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\Clock\FixedClock
 */
class FixedClockTest extends TestCase
{
    public function testClock(): void
    {
        $clock = new FixedClock(new \DateTime('2018-09-28 19:12:32.763188 +00:00'));
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        self::assertEquals($now, $clock->now());
    }
}
