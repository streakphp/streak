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
use Streak\Domain\Event\Subscription;
use Streak\Domain\Id;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Subscription\Exception\SubscriptionAlreadyStarted
 */
class SubscriptionAlreadyStartedTest extends TestCase
{
    /**
     * @var Subscription|MockObject
     */
    private $subscription;

    /**
     * @var Id|MockObject
     */
    private $subscriptionId;

    protected function setUp()
    {
        $this->subscription = $this->getMockBuilder(Subscription::class)->getMockForAbstractClass();
        $this->subscriptionId = $this->getMockBuilder(Id::class)->setMockClassName('test_id')->getMockForAbstractClass();
    }

    public function testException()
    {
        $this->subscription
            ->expects($this->atLeastOnce())
            ->method('subscriptionId')
            ->willReturn($this->subscriptionId)
        ;

        $this->subscriptionId
            ->expects($this->atLeastOnce())
            ->method('toString')
            ->willReturn('8c2e12ea-4cb4-4e9a-a840-a3160ca20224')
        ;

        $exception = new SubscriptionAlreadyStarted($this->subscription);

        $this->assertSame($this->subscription, $exception->subscription());
        $this->assertSame('Subscription "test_id#8c2e12ea-4cb4-4e9a-a840-a3160ca20224" is already started.', $exception->getMessage());
    }
}
