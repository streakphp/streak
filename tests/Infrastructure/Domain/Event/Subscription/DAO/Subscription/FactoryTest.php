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

namespace Streak\Infrastructure\Domain\Event\Subscription\DAO\Subscription;

use PHPUnit\Framework\TestCase;
use Streak\Domain\Clock;
use Streak\Domain\Event;
use Streak\Infrastructure\Domain\Event\Subscription\DAO\Subscription;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\Event\Subscription\DAO\Subscription\Factory
 */
class FactoryTest extends TestCase
{
    private Event\Listener $listener;

    private Clock $clock;

    protected function setUp(): void
    {
        $this->listener = $this->getMockBuilder(Event\Listener::class)->getMockForAbstractClass();
        $this->clock = $this->getMockBuilder(Clock::class)->getMockForAbstractClass();
    }

    public function testFactory(): void
    {
        $factory = new Factory($this->clock);

        $subscription = $factory->create($this->listener);

        self::assertEquals(new Subscription($this->listener, $this->clock), $subscription);
    }
}
