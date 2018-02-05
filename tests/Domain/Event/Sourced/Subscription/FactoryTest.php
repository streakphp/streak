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

namespace Streak\Domain\Event\Sourced\Subscription;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Sourced\Subscription\Factory
 */
class FactoryTest extends TestCase
{
    /**
     * @var Event\Listener|MockObject
     */
    private $listener;

    public function setUp()
    {
        $this->listener = $this->getMockBuilder(Event\Listener::class)->getMockForAbstractClass();
    }

    public function testFactory()
    {
        $factory = new Factory();

        $subscription = $factory->create($this->listener);

        $this->assertInstanceOf(Event\Sourced\Subscription::class, $subscription);
    }
}
