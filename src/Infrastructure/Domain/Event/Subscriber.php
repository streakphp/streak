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

namespace Streak\Infrastructure\Domain\Event;

use Streak\Domain\Event;
use Streak\Domain\Event\Exception;
use Streak\Domain\EventBus;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionCompleted;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionListenedToEvent;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionStarted;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Domain\Event\SubscriberTest
 */
class Subscriber implements \Streak\Application\Event\Listener
{
    use \Streak\Application\Event\Listener\Identifying;

    private \Streak\Application\Event\Listener\Factory $listenerFactory;
    private \Streak\Application\Event\Listener\Subscription\Factory $subscriptionFactory;
    private \Streak\Application\Event\Listener\Subscription\Repository $subscriptionsRepository;

    public function __construct(\Streak\Application\Event\Listener\Factory $listenerFactory, \Streak\Application\Event\Listener\Subscription\Factory $subscriptionFactory, \Streak\Application\Event\Listener\Subscription\Repository $subscriptionsRepository)
    {
        $this->identifyBy(Subscriber\Id::random());
        $this->listenerFactory = $listenerFactory;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->subscriptionsRepository = $subscriptionsRepository;
    }

    public function listenTo(EventBus $bus): void
    {
        $bus->add($this);
    }

    public function on(Event\Envelope $event): bool
    {
        // TODO: move filtering subscription-events to subscriber decorator or listener factory decorator.
        if ($event->message() instanceof SubscriptionStarted) {
            return false;
        }

        if ($event->message() instanceof SubscriptionListenedToEvent) {
            return false;
        }

        if ($event->message() instanceof SubscriptionCompleted) {
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
