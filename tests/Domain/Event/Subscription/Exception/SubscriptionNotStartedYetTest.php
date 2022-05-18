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

namespace Streak\Domain\Event\Subscription\Exception;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Subscription;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Subscription\Exception\SubscriptionNotStartedYet
 */
class SubscriptionNotStartedYetTest extends TestCase
{
    private Subscription|MockObject $subscription;

    private Listener\Id|MockObject $subscriptionId;

    protected function setUp(): void
    {
        $this->subscription = $this->getMockBuilder(Subscription::class)->getMockForAbstractClass();
        $this->subscriptionId = $this->getMockBuilder(Listener\Id::class)->setMockClassName('test_id')->getMockForAbstractClass();
    }

    public function testException(): void
    {
        $this->subscription
            ->expects(self::atLeastOnce())
            ->method('id')
            ->willReturn($this->subscriptionId)
        ;

        $this->subscriptionId
            ->expects(self::atLeastOnce())
            ->method('toString')
            ->willReturn('8c2e12ea-4cb4-4e9a-a840-a3160ca20224')
        ;

        $exception = new SubscriptionNotStartedYet($this->subscription);

        self::assertSame($this->subscription, $exception->subscription());
        self::assertSame('Subscription "test_id#8c2e12ea-4cb4-4e9a-a840-a3160ca20224" is not started yet.', $exception->getMessage());
    }
}
