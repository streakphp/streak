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
        $now = new \DateTime('2018-09-28 19:12:32.763188+00:00');
        $clock = new FixedClock($now);

        $this->assertEquals($now, $clock->now());

        $now = new \DateTime('2018-09-28 19:54:12.563188+00:00');

        $clock->timeIs($now);

        $this->assertEquals($now, $clock->now());
    }
}
