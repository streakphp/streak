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

namespace Streak\Infrastructure\Event\Subscription\DAO;

use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Subscription;
use Streak\Infrastructure\Event\Subscription\DAO;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Event\Subscription\DAO\InMemoryDAOTest
 */
class InMemoryDAO implements DAO
{
    /**
     * @var Subscription[]
     */
    private array $subscriptions = [];

    public function save(Subscription $subscription): void
    {
        foreach ($this->subscriptions as $key => $stored) {
            if ($stored->subscriptionId()->equals($subscription->subscriptionId())) {
                $this->subscriptions[$key] = $subscription;

                return;
            }
        }

        $this->subscriptions[] = $subscription;
    }

    public function one(Listener\Id $id): ?Subscription
    {
        foreach ($this->subscriptions as $key => $stored) {
            if ($stored->subscriptionId()->equals($id)) {
                return $stored;
            }
        }

        return null;
    }

    public function exists(Listener\Id $id): bool
    {
        return null !== $this->one($id);
    }

    public function all(array $types = [], ?bool $completed = null): iterable
    {
        foreach ($this->subscriptions as $key => $stored) {
            if (\count($types)) {
                $type = \get_class($stored->subscriptionId());
                if (false === \in_array($type, $types)) {
                    continue;
                }
            }

            if (true === $completed) {
                if (false === $stored->completed()) {
                    continue;
                }
            } elseif (false === $completed) {
                if (true === $stored->completed()) {
                    continue;
                }
            }

            yield $stored;
        }
    }

    public function clear(): void
    {
        $this->subscriptions = [];
    }
}
