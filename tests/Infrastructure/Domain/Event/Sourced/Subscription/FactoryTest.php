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

namespace Streak\Infrastructure\Domain\Event\Sourced\Subscription;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Clock;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\Event\Sourced\Subscription\Factory
 */
class FactoryTest extends TestCase
{
    private Event\Listener|MockObject $listener;

    private Clock|MockObject $clock;

    protected function setUp(): void
    {
        $this->listener = $this->getMockBuilder(Event\Listener::class)->getMockForAbstractClass();
        $this->clock = $this->getMockBuilder(Clock::class)->getMockForAbstractClass();
    }

    public function testFactory(): void
    {
        $factory = new Factory($this->clock);

        $subscription = $factory->create($this->listener);

        self::assertEquals(new \Streak\Infrastructure\Domain\Event\Sourced\Subscription($this->listener, $this->clock), $subscription);
    }
}
