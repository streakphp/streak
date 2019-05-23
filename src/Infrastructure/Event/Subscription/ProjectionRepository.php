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

namespace Streak\Infrastructure\Event\Subscription;

use Streak\Application\Listener\Subscriptions\Projector\Query\ListSubscriptions;
use Streak\Application\QueryBus;
use Streak\Domain\Event;
use Streak\Domain\Event\Subscription\Repository;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class ProjectionRepository implements Repository
{
    private $fallback;
    private $bus;

    public function __construct(Repository $fallback, QueryBus $bus)
    {
        $this->fallback = $fallback;
        $this->bus = $bus;
    }

    public function find(Event\Listener\Id $id) : ?Event\Subscription
    {
        return $this->fallback->find($id);
    }

    public function has(Event\Subscription $subscription) : bool
    {
        return $this->fallback->has($subscription);
    }

    public function add(Event\Subscription $subscription) : void
    {
        $this->fallback->add($subscription);
    }

    public function all(?Repository\Filter $filter = null) : iterable
    {
        if (null === $filter) {
            $filter = Repository\Filter::nothing();
        }

        try {
            $rows = $this->bus->dispatch(new ListSubscriptions($filter->subscriptionTypes(), $filter->areCompletedSubscriptionsIgnored() ? false : null));
        } catch (\Exception $exception) {
            yield from $this->fallback->all($filter);

            return;
        }

        foreach ($rows as $row) {
            $reflection = new \ReflectionClass($row['subscription_type']);
            $method = $reflection->getMethod('fromString');
            $id = $method->invoke(null, $row['subscription_id']);

            $subscription = $this->find($id);

            if (null === $subscription) {
                continue;
            }

            if (true === $filter->areCompletedSubscriptionsIgnored() && true === $subscription->completed()) {
                continue;
            }

            yield $subscription;
        }
    }
}
