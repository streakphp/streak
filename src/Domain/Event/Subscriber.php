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

use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\EventBus;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class Subscriber implements Listener
{
    private $uuid;
    private $listenerFactory;
    private $subscriptionFactory;
    private $subscriptions;

    public function __construct(Event\Listener\Factory $listenerFactory, Event\Subscription\Factory $subscriptionFactory, Event\Subscription\Repository $subscriptions)
    {
        $this->uuid = Domain\Id\UUID::create();
        $this->listenerFactory = $listenerFactory;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->subscriptions = $subscriptions;
    }

    public function id() : Domain\Id
    {
        return $this->uuid;
    }

    public function listenTo(EventBus $bus)
    {
        $bus->add($this);
    }

    public function on(Event $event) : bool
    {
        try {
            $listener = $this->listenerFactory->createFor($event);
        } catch (Exception\InvalidEventGiven $e) {
            return false;
        }

        $subscription = $this->subscriptions->findFor($listener);

        if (null === $subscription) {
            $subscription = $this->subscriptionFactory->create($listener);
            $subscription->start(new \DateTime());

            $this->subscriptions->add($subscription);
        }

        return true;
    }
}
