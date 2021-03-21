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

namespace Streak\Domain\Event\Sourced\Subscription\Repository;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Subscription\Repository\Filter;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Subscription\Repository\Filter
 */
class FilterTest extends TestCase
{
    /**
     * @var Listener\Id|MockObject
     */
    private $id1;

    /**
     * @var Listener\Id|MockObject
     */
    private $id2;

    protected function setUp() : void
    {
        $this->id1 = $this->getMockBuilder(Listener\Id::class)->setMockClassName('id1')->getMock();
        $this->id2 = $this->getMockBuilder(Listener\Id::class)->setMockClassName('id2')->getMockForAbstractClass();
    }

    public function testFilter()
    {
        $filter = Filter::nothing();

        $this->assertEquals(new Filter(), $filter);
        $this->assertEmpty($filter->subscriptionTypes());
        $this->assertTrue($filter->areCompletedSubscriptionsIgnored());

        $filter = $filter->doNotIgnoreCompletedSubscriptions();

        $this->assertEmpty($filter->subscriptionTypes());
        $this->assertFalse($filter->areCompletedSubscriptionsIgnored());

        $filter = $filter->filterSubscriptionTypes('type-1');

        $this->assertEquals(['type-1'], $filter->subscriptionTypes());
        $this->assertFalse($filter->areCompletedSubscriptionsIgnored());

        $filter = $filter->filterSubscriptionTypes('type-2');

        $this->assertEquals(['type-1', 'type-2'], $filter->subscriptionTypes());
        $this->assertFalse($filter->areCompletedSubscriptionsIgnored());

        $filter = $filter->filterSubscriptionTypes('type-1');
        $filter = $filter->filterSubscriptionTypes('type-2');

        $this->assertEquals(['type-1', 'type-2'], $filter->subscriptionTypes());
        $this->assertFalse($filter->areCompletedSubscriptionsIgnored());

        $filter = $filter->ignoreCompletedSubscriptions();

        $this->assertEquals(['type-1', 'type-2'], $filter->subscriptionTypes());
        $this->assertTrue($filter->areCompletedSubscriptionsIgnored());

        $filter = $filter->doNotFilterProducerTypes();

        $this->assertEquals(new Filter(), $filter);
        $this->assertEquals(new Filter(), $filter);
        $this->assertEmpty($filter->subscriptionTypes());
        $this->assertTrue($filter->areCompletedSubscriptionsIgnored());
    }
}
