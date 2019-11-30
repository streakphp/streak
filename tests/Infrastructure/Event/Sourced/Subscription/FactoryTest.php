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

namespace Streak\Infrastructure\Event\Sourced\Subscription;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Clock;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Event\Sourced\Subscription\Factory
 */
class FactoryTest extends TestCase
{
    /**
     * @var Event\Listener|MockObject
     */
    private $listener;

    /**
     * @var Clock|MockObject
     */
    private $clock;

    public function setUp()
    {
        $this->listener = $this->getMockBuilder(Event\Listener::class)->getMockForAbstractClass();
        $this->clock = $this->getMockBuilder(Clock::class)->getMockForAbstractClass();
    }

    public function testFactory()
    {
        $factory = new Factory($this->clock);

        $subscription = $factory->create($this->listener);

        $this->assertEquals(new \Streak\Infrastructure\Event\Sourced\Subscription($this->listener, $this->clock), $subscription);
    }
}
