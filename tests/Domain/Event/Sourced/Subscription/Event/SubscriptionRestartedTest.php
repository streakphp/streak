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

namespace Streak\Domain\Event\Sourced\Subscription\Event;

use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\Id\UUID;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionRestarted
 */
class SubscriptionRestartedTest extends TestCase
{
    private $event;

    protected function setUp(): void
    {
        $this->event = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $this->event = Event\Envelope::new($this->event, UUID::random());
    }

    public function testObject(): void
    {
        $event = new SubscriptionRestarted($this->event, $now = new \DateTimeImmutable());

        self::assertSame($this->event, $event->originallyStartedBy());
        self::assertEquals($now, $event->timestamp());
    }
}
