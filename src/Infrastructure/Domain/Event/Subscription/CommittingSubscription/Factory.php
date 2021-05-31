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
use Streak\Application\Event\Listener\Subscription;
use Streak\Infrastructure\Domain\Event\Subscription\CommittingSubscription;
use Streak\Infrastructure\Domain\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Domain\Event\Subscription\CommittingSubscription\FactoryTest
 */
class Factory implements Subscription\Factory
{
    private Subscription\Factory $factory;
    private UnitOfWork $uow;

    public function __construct(Subscription\Factory $factory, UnitOfWork $uow)
    {
        $this->factory = $factory;
        $this->uow = $uow;
    }

    public function create(\Streak\Application\Event\Listener $listener): \Streak\Application\Event\Listener\Subscription
    {
        $subscription = $this->factory->create($listener);

        return new CommittingSubscription($subscription, $this->uow);
    }
}
