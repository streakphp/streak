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

namespace Streak\Infrastructure\Event\Subscription\CommittingSubscription;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Clock;
use Streak\Domain\Event;
use Streak\Infrastructure\Event\Sourced\Subscription;
use Streak\Infrastructure\Event\Subscription\CommittingSubscription;
use Streak\Infrastructure\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Event\Subscription\CommittingSubscription\Factory
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

    /**
     * @var UnitOfWork|MockObject
     */
    private $uow;

    public function setUp()
    {
        $this->listener = $this->getMockBuilder(Event\Listener::class)->getMockForAbstractClass();
        $this->clock = $this->getMockBuilder(Clock::class)->getMockForAbstractClass();
        $this->uow = $this->getMockBuilder(UnitOfWork::class)->getMockForAbstractClass();
    }

    public function testObject()
    {
        $factory = new Subscription\Factory($this->clock);
        $factory = new CommittingSubscription\Factory($factory, $this->uow);

        $actual = $factory->create($this->listener);

        $expected = new CommittingSubscription(new Subscription($this->listener, $this->clock), $this->uow);

        $this->assertEquals($expected, $actual);
    }
}
