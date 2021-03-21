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

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
final class Filter
{
    private array $subscriptionTypes = [];
    private bool $areCompletedSubscriptionsIgnored = true;

    public function __construct()
    {
    }

    public static function nothing() : Filter
    {
        return new self();
    }

    public function filterSubscriptionTypes(string ...$types) : Filter
    {
        $filter = new self();
        $filter->subscriptionTypes = $this->subscriptionTypes;
        $filter->areCompletedSubscriptionsIgnored = $this->areCompletedSubscriptionsIgnored;

        foreach ($types as $type) {
            if (true === in_array($type, $filter->subscriptionTypes, true)) {
                continue;
            }
            $filter->subscriptionTypes[] = $type;
        }

        return $filter;
    }

    public function doNotFilterProducerTypes() : Filter
    {
        $filter = new self();
        $filter->subscriptionTypes = [];
        $filter->areCompletedSubscriptionsIgnored = $this->areCompletedSubscriptionsIgnored;

        return $filter;
    }

    public function subscriptionTypes() : array
    {
        return $this->subscriptionTypes;
    }

    public function ignoreCompletedSubscriptions() : Filter
    {
        $filter = new self();
        $filter->subscriptionTypes = $this->subscriptionTypes;
        $filter->areCompletedSubscriptionsIgnored = true;

        return $filter;
    }

    public function doNotIgnoreCompletedSubscriptions() : Filter
    {
        $filter = new self();
        $filter->subscriptionTypes = $this->subscriptionTypes;
        $filter->areCompletedSubscriptionsIgnored = false;

        return $filter;
    }

    public function areCompletedSubscriptionsIgnored() : bool
    {
        return $this->areCompletedSubscriptionsIgnored;
    }
}
