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
use Streak\Domain\Event;
use Streak\Domain\Id\UUID;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionStarted
 */
class SubscriptionStartedTest extends TestCase
{
    private Event\Envelope $event;

    protected function setUp(): void
    {
        $this->event = Event\Envelope::new($this->getMockBuilder(Event::class)->getMockForAbstractClass(), UUID::random());
    }

    public function testObject(): void
    {
        $event = new SubscriptionStarted($this->event, $now = new \DateTime());

        self::assertSame($this->event, $event->startedBy());
        self::assertEquals($now, $event->timestamp());
    }
}
