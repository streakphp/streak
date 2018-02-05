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
use Streak\Domain\Event\Subscription;
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
     * @var Domain\EventStore
     */
    private $store;

    /**
     * @var Infrastructure\UnitOfWork
     */
    private $uow;

    public function __construct(Subscription\Factory $subscriptions, Domain\EventStore $store, Infrastructure\UnitOfWork $uow)
    {
        $this->subscriptions = $subscriptions;
        $this->store = $store;
        $this->uow = $uow;
    }

    public function findFor(Event\Listener $listener) : ?Event\Subscription
    {
        $subscription = $this->subscriptions->create($listener);

        if (!$subscription instanceof Domain\Event\Sourced\Subscription) {
            throw new Exception\ObjectNotSupported($subscription);
        }

        $stream = $this->store->stream($subscription->producerId());

        if ($stream->empty()) {
            return null;
        }

        $subscription->replay($stream);

        $this->uow->add($subscription);

        return $subscription;
    }

    public function add(Event\Subscription $subscription) : void
    {
        if (!$subscription instanceof Domain\Event\Sourced\Subscription) {
            throw new Exception\ObjectNotSupported($subscription);
        }

        $this->uow->add($subscription);
    }

    public function all() : iterable
    {
    }
}
