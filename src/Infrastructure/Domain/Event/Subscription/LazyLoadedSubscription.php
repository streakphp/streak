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

namespace Streak\Infrastructure\Domain\Event\Subscription;

use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Subscription;
use Streak\Domain\EventStore;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Domain\Event\Subscription\LazyLoadedSubscriptionTest
 */
class LazyLoadedSubscription implements Subscription, Subscription\Decorator
{
    private ?Subscription $subscription = null;

    public function __construct(private Listener\Id $id, private Subscription\Repository $repository)
    {
    }

    public function id(): Listener\Id
    {
        return $this->id;
    }

    public function listener(): Listener
    {
        return $this->subscription()->listener();
    }

    public function subscribeTo(EventStore $store, ?int $limit = null): iterable
    {
        yield from $this->subscription()->subscribeTo($store, $limit);
    }

    public function startFor(Event\Envelope $event): void
    {
        $this->subscription()->startFor($event);
    }

    public function restart(): void
    {
        $this->subscription()->restart();
    }

    public function starting(): bool
    {
        return $this->subscription()->starting();
    }

    public function started(): bool
    {
        return $this->subscription()->started();
    }

    public function completed(): bool
    {
        return $this->subscription()->completed();
    }

    public function subscription(): Subscription
    {
        if (null === $this->subscription) {
            $this->subscription = $this->repository->find($this->id());
        }

        return $this->subscription;
    }

    public function paused(): bool
    {
        return $this->subscription->paused();
    }

    public function pause(): void
    {
        $this->subscription->pause();
    }

    public function unpause(): void
    {
        $this->subscription->unpause();
    }

    public function version(): int
    {
        return $this->subscription()->version();
    }
}
