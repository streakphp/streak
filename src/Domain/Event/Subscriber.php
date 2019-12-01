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

namespace Streak\Domain\Event;

use Streak\Domain\Event;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionCompleted;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionListenedToEvent;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionStarted;
use Streak\Domain\EventBus;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * TODO: move under `Infrastructure`
 */
class Subscriber implements Event\Listener
{
    use Event\Listener\Identifying;

    private $listenerFactory;
    private $subscriptionFactory;
    private $subscriptionsRepository;

    public function __construct(Event\Listener\Factory $listenerFactory, Event\Subscription\Factory $subscriptionFactory, Event\Subscription\Repository $subscriptionsRepository)
    {
        $this->identifyBy(Subscriber\Id::create());
        $this->listenerFactory = $listenerFactory;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->subscriptionsRepository = $subscriptionsRepository;
    }

    public function listenTo(EventBus $bus)
    {
        $bus->add($this);
    }

    public function on(Event $event) : bool
    {
        // TODO: move filtering subscription-events to subscriber decorator or listener factory decorator.
        if ($event instanceof SubscriptionStarted) {
            return false;
        }

        if ($event instanceof SubscriptionListenedToEvent) {
            return false;
        }

        if ($event instanceof SubscriptionCompleted) {
            return false;
        }

        try {
            $listener = $this->listenerFactory->createFor($event);
        } catch (Exception\InvalidEventGiven $e) {
            return false;
        }

        $subscription = $this->subscriptionFactory->create($listener);

        if (true === $this->subscriptionsRepository->has($subscription)) {
            return true;
        }

        $this->subscriptionsRepository->add($subscription);

        $subscription->startFor($event);

        return true;
    }
}
