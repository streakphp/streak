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

namespace Streak\Infrastructure\Domain\Event\Subscription\CommittingSubscription;

use Streak\Domain\Event;
use Streak\Domain\Event\Subscription;
use Streak\Infrastructure\Domain\Event\Subscription\CommittingSubscription;
use Streak\Infrastructure\Domain\UnitOfWork;

/**
 * @implements Event\Subscription\Factory<CommittingSubscription>
 *
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Domain\Event\Subscription\CommittingSubscription\FactoryTest
 */
class Factory implements Subscription\Factory
{
    public function __construct(private Subscription\Factory $factory, private UnitOfWork $uow)
    {
    }

    public function create(Event\Listener $listener): CommittingSubscription
    {
        $subscription = $this->factory->create($listener);

        return new CommittingSubscription($subscription, $this->uow);
    }
}
