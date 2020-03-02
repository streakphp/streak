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

namespace Streak\Infrastructure\UnitOfWork;

use Streak\Domain\Event\Subscription;
use Streak\Infrastructure\Event\Subscription\DAO;
use Streak\Infrastructure\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class SubscriptionDAOUnitOfWork implements UnitOfWork
{
    private $dao;

    /**
     * @var Subscription[]
     */
    private $uncommited = [];

    private $committing = false;

    public function __construct(DAO $dao)
    {
        $this->dao = $dao;
    }

    public function add($subscription) : void
    {
        if (false === $this->supports($subscription)) {
            throw new UnitOfWork\Exception\ObjectNotSupported($subscription);
        }

        if (!$this->has($subscription)) {
            $this->uncommited[] = $subscription;
        }
    }

    public function remove($subscription) : void
    {
        if (false === $this->supports($subscription)) {
            throw new UnitOfWork\Exception\ObjectNotSupported($subscription);
        }

        foreach ($this->uncommited as $key => $current) {
            if ($current->subscriptionId()->equals($subscription->subscriptionId())) {
                unset($this->uncommited[$key]);

                return;
            }
        }
    }

    public function has($subscription) : bool
    {
        if (false === $this->supports($subscription)) {
            throw new UnitOfWork\Exception\ObjectNotSupported($subscription);
        }

        foreach ($this->uncommited as $current) {
            if ($current->subscriptionId()->equals($subscription->subscriptionId())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return DAO\Subscription[]
     */
    public function uncommitted() : array
    {
        return array_values($this->uncommited);
    }

    public function count() : int
    {
        return count($this->uncommited);
    }

    public function commit() : \Generator
    {
        if (false === $this->committing) {
            $this->committing = true;

            try {
                /** @var $subscription Subscription */
                while ($subscription = array_shift($this->uncommited)) {
                    try {
                        $this->dao->save($subscription);

                        yield $subscription;
                    } catch (\Exception $e) {
                        // something unexpected occurred, so lets leave uow in state from just before it happened - we may like to retry it later...
                        array_unshift($this->uncommited, $subscription);
                        throw $e;
                    }
                }

                $this->clear();
            } finally {
                $this->committing = false;
            }
        }
    }

    public function clear() : void
    {
        $this->uncommited = [];
    }

    private function supports(Subscription $subscription) : bool
    {
        if ($subscription instanceof DAO\Subscription) {
            return true;
        }

        while ($subscription instanceof Subscription\Decorator) {
            $subscription = $subscription->subscription();

            if ($subscription instanceof DAO\Subscription) {
                return true;
            }
        }

        return false;
    }
}
