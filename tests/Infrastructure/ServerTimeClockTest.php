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
 * @covers \Streak\Infrastructure\ServerTimeClock
 */
class ServerTimeClockTest extends TestCase
{
    public function testClock()
    {
        $clock = new ServerTimeClock();

        $now = new \DateTime();

        $this->assertSame($clock->now()->format(\DateTime::ATOM), $now->format(\DateTime::ATOM));
    }
}
