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

namespace Streak\Infrastructure\Domain\Event\Subscription\CommittingSubscription;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Clock;
use Streak\Domain\Event;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription;
use Streak\Infrastructure\Domain\Event\Subscription\CommittingSubscription;
use Streak\Infrastructure\Domain\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\Event\Subscription\CommittingSubscription\Factory
 */
class FactoryTest extends TestCase
{
    private Event\Listener|MockObject $listener;

    private Clock|MockObject $clock;

    private UnitOfWork|MockObject $uow;

    protected function setUp(): void
    {
        $this->listener = $this->getMockBuilder(Event\Listener::class)->getMockForAbstractClass();
        $this->clock = $this->getMockBuilder(Clock::class)->getMockForAbstractClass();
        $this->uow = $this->getMockBuilder(UnitOfWork::class)->getMockForAbstractClass();
    }

    public function testObject(): void
    {
        $factory = new Subscription\Factory($this->clock);
        $factory = new CommittingSubscription\Factory($factory, $this->uow);

        $actual = $factory->create($this->listener);

        $expected = new CommittingSubscription(new Subscription($this->listener, $this->clock), $this->uow);

        self::assertEquals($expected, $actual);
    }
}
