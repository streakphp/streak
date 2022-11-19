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

namespace Streak\Domain\Event\Subscription\Repository;

use PHPUnit\Framework\TestCase;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Subscription\Repository\Filter
 */
class FilterTest extends TestCase
{
    public function testFilter(): void
    {
        $filter = Filter::nothing();

        self::assertEquals(new Filter(), $filter);
        self::assertEmpty($filter->subscriptionTypes());
        self::assertTrue($filter->areCompletedSubscriptionsIgnored());

        $filter = $filter->doNotIgnoreCompletedSubscriptions();

        self::assertEmpty($filter->subscriptionTypes());
        self::assertFalse($filter->areCompletedSubscriptionsIgnored());

        $filter = $filter->filterSubscriptionTypes('type-1');

        self::assertEquals(['type-1'], $filter->subscriptionTypes());
        self::assertFalse($filter->areCompletedSubscriptionsIgnored());

        $filter = $filter->filterSubscriptionTypes('type-2');

        self::assertEquals(['type-1', 'type-2'], $filter->subscriptionTypes());
        self::assertFalse($filter->areCompletedSubscriptionsIgnored());

        $filter = $filter->filterSubscriptionTypes('type-1');
        $filter = $filter->filterSubscriptionTypes('type-2');

        self::assertEquals(['type-1', 'type-2'], $filter->subscriptionTypes());
        self::assertFalse($filter->areCompletedSubscriptionsIgnored());

        $filter = $filter->ignoreCompletedSubscriptions();

        self::assertEquals(['type-1', 'type-2'], $filter->subscriptionTypes());
        self::assertTrue($filter->areCompletedSubscriptionsIgnored());

        $filter = $filter->doNotFilterProducerTypes();

        self::assertEquals(new Filter(), $filter);
        self::assertEquals(new Filter(), $filter);
        self::assertEmpty($filter->subscriptionTypes());
        self::assertTrue($filter->areCompletedSubscriptionsIgnored());
    }
}
