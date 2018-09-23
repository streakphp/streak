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

namespace Streak\Infrastructure;

use PHPUnit\Framework\TestCase;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\FixedClock
 */
class FixedClockTest extends TestCase
{
    public function testClock()
    {
        $clock = new FixedClock(new \DateTime('2018-09-28 19:12:32.763188 +00:00'));
        $now = new \DateTime('2018-09-28 19:12:32.763188 +00:00');

        $this->assertEquals($now, $clock->now());
    }
}
