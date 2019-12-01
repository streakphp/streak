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

use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionCompleted;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionRestarted;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionStarted;
use Streak\Domain\Event\Subscription;
use Streak\Domain\Event\Subscription\Repository\Filter;
use Streak\Domain\EventStore;
use Streak\Domain\Exception;
use Streak\Infrastructure;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class EventSourcedRepository implements Subscription\Repository
{
    /**
     * @var Subscription\Factory
     */
    private $subscriptions;

    /**
     * @var Event\Listener\Factory
     */
    private $listeners;

    /**
     * @var Domain\EventStore
     */
    private $store;

    /**
     * @var Infrastructure\UnitOfWork
     */
    private $uow;

    public function __construct(Subscription\Factory $subscriptions, Event\Listener\Factory $listeners, Domain\EventStore $store, Infrastructure\UnitOfWork $uow)
    {
        $this->subscriptions = $subscriptions;
        $this->listeners = $listeners;
        $this->store = $store;
        $this->uow = $uow;
    }

    public function find(Event\Listener\Id $id) : ?Event\Subscription
    {
        $listener = $this->listeners->create($id);
        $subscription = $this->subscriptions->create($listener);

        if (!$subscription instanceof Event\Sourced) {
            throw new Exception\ObjectNotSupported($subscription);
        }

        $filter = new EventStore\Filter();
        $filter = $filter->filterProducerIds($subscription->producerId());

        $stream = $this->store->stream($filter);

        if ($stream->empty()) {
            return null;
        }

        $reset = $stream->only(SubscriptionRestarted::class)->last();

        if (null !== $reset) {
            $stream = $stream->from($reset);
        }

        $subscription->replay($stream);

        $this->uow->add($subscription);

        return $subscription;
    }

    /**
     * @throws Exception\ObjectNotSupported
     */
    public function has(Event\Subscription $subscription) : bool
    {
        if (!$subscription instanceof Event\Sourced) {
            throw new Exception\ObjectNotSupported($subscription);
        }

        $filter = new EventStore\Filter();
        $filter = $filter->filterProducerIds($subscription->producerId());

        $stream = $this->store->stream($filter);

        if ($stream->empty()) {
            return false;
        }

        return true;
    }

    /**
     * @throws Exception\ObjectNotSupported
     */
    public function add(Event\Subscription $subscription) : void
    {
        if (!$subscription instanceof Event\Sourced) {
            throw new Exception\ObjectNotSupported($subscription);
        }

        $this->uow->add($subscription);
    }

    /**
     * @return iterable|Event\Subscription[]
     */
    public function all(?Filter $filter = null) : iterable
    {
        if (null === $filter) {
            $filter = Filter::nothing();
        }

        $streamFilter = new EventStore\Filter();
        $streamFilter = $streamFilter->filterProducerTypes(...$filter->subscriptionTypes());

        $stream = $this->store->stream($streamFilter);
        $stream = $stream->only(SubscriptionStarted::class, SubscriptionRestarted::class, SubscriptionCompleted::class);

        $ids = [];
        foreach ($stream as $event) {
            $id = $this->store->producerId($event);

            if ($event instanceof SubscriptionStarted) {
                $ids[] = $id;
            }

            if (true === $filter->areCompletedSubscriptionsIgnored()) {
                if ($event instanceof SubscriptionCompleted) {
                    if (false !== ($key = array_search($id, $ids))) { // TODO: make it look nicer
                        unset($ids[$key]);
                    }
                }
            }

            if ($event instanceof SubscriptionRestarted) {
                if (false === ($key = array_search($id, $ids))) { // TODO: make it look nicer
                    $ids[] = $id;
                }
            }
        }

        foreach ($ids as $id) {
            yield new LazyLoadedSubscription($id, $this);
        }
    }
}
