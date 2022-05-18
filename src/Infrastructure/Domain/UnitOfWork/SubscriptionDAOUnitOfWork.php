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

namespace Streak\Infrastructure\Domain\UnitOfWork;

use Streak\Domain\Event\Subscription;
use Streak\Infrastructure\Domain\Event\Subscription\DAO;
use Streak\Infrastructure\Domain\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @template-implements UnitOfWork<DAO\Subscription>
 *
 * @see \Streak\Infrastructure\Domain\UnitOfWork\SubscriptionDAOUnitOfWorkTest
 */
class SubscriptionDAOUnitOfWork implements UnitOfWork
{
    /**
     * @var Subscription[]
     */
    private array $uncommited = [];

    private bool $committing = false;

    public function __construct(private DAO $dao)
    {
    }

    public function add(object $object): void
    {
        if (false === $this->supports($object)) {
            throw new UnitOfWork\Exception\ObjectNotSupported($object);
        }

        if (!$this->has($object)) {
            $this->uncommited[] = $object;
        }
    }

    public function remove(object $object): void
    {
        if (false === $this->supports($object)) {
            throw new UnitOfWork\Exception\ObjectNotSupported($object);
        }

        foreach ($this->uncommited as $key => $current) {
            if ($current->id()->equals($object->id())) {
                unset($this->uncommited[$key]);

                return;
            }
        }
    }

    public function has(object $object): bool
    {
        if (false === $this->supports($object)) {
            throw new UnitOfWork\Exception\ObjectNotSupported($object);
        }

        foreach ($this->uncommited as $current) {
            if ($current->id()->equals($object->id())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return DAO\Subscription[]
     */
    public function uncommitted(): array
    {
        return array_values($this->uncommited);
    }

    public function count(): int
    {
        return \count($this->uncommited);
    }

    public function commit(): \Generator
    {
        if (false === $this->committing) {
            $this->committing = true;

            try {
                while ($object = array_shift($this->uncommited)) {
                    /** @var Subscription $object */
                    try {
                        $this->dao->save($object);

                        yield $object;
                    } catch (\Exception $e) {
                        // something unexpected occurred, so lets leave uow in state from just before it happened - we may like to retry it later...
                        array_unshift($this->uncommited, $object);

                        throw $e;
                    }
                }

                $this->clear();
            } finally {
                $this->committing = false;
            }
        }
    }

    public function clear(): void
    {
        $this->uncommited = [];
    }

    private function supports(object $subscription): bool
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
