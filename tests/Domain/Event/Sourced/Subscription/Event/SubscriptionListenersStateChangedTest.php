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
use Streak\Infrastructure\Event\Sourced\Subscription\InMemoryState;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionListenersStateChanged
 */
class SubscriptionListenersStateChangedTest extends TestCase
{
    private ?InMemoryState $state = null;

    protected function setUp(): void
    {
        $this->state = InMemoryState::empty();
        $this->state = $this->state->set('name', 'value');
    }

    public function testObject(): void
    {
        $event = new SubscriptionListenersStateChanged($this->state, $now = new \DateTimeImmutable());

        self::assertEquals($now, $event->timestamp());
        self::assertNotSame($event->state(), $event->state());
        self::assertTrue($event->state()->equals($this->state));
        self::assertTrue($event->state()->equals($event->state()));
    }
}
