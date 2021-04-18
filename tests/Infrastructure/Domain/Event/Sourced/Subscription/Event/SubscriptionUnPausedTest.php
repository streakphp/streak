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

namespace Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event;

use PHPUnit\Framework\TestCase;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionUnPaused
 */
class SubscriptionUnPausedTest extends TestCase
{
    public function testObject(): void
    {
        $event = new SubscriptionUnPaused($now = new \DateTimeImmutable());

        self::assertEquals($now, $event->timestamp());
    }
}
