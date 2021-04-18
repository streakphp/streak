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

namespace Streak\Infrastructure\Domain\Event\Subscription\DAO;

use Streak\Domain\Event;
use Streak\Domain\Event\Subscription;
use Streak\Infrastructure\Domain\Event\Subscription\DAO;
use Streak\Infrastructure\Domain\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Domain\Event\Subscription\DAO\DAORepositoryTest
 */
class DAORepository implements Subscription\Repository
{
    private DAO $dao;

    private UnitOfWork $uow;

    public function __construct(DAO $dao, UnitOfWork $uow)
    {
        $this->dao = $dao;
        $this->uow = $uow;
    }

    public function find(Event\Listener\Id $id): ?Event\Subscription
    {
        $subscription = $this->dao->one($id);

        if (null === $subscription) {
            return null;
        }

        $this->uow->add($subscription);

        return $subscription;
    }

    public function has(Event\Subscription $subscription): bool
    {
        return $this->dao->exists($subscription->subscriptionId());
    }

    public function add(Event\Subscription $subscription): void
    {
        $this->uow->add($subscription);
    }

    public function all(?Subscription\Repository\Filter $filter = null): iterable
    {
        if (null === $filter) {
            $filter = Subscription\Repository\Filter::nothing();
        }
        $types = $filter->subscriptionTypes();
        $completed = $filter->areCompletedSubscriptionsIgnored() ? false : null;

        foreach ($this->dao->all($types, $completed) as $subscription) {
            $this->add($subscription);

            yield $subscription;
        }
    }
}
