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

namespace Streak\Infrastructure\Event\Subscription\DbalTransactionalSubscription;

use Doctrine\DBAL\Connection;
use Streak\Domain\Event;
use Streak\Infrastructure\Event\Subscription\DbalTransactionalSubscription;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Event\Subscription\DbalTransactionalSubscription\FactoryTest
 */
class Factory implements Event\Subscription\Factory
{
    private Event\Subscription\Factory $factory;
    private Connection $connection;
    private $maxTransactionSize;

    public function __construct(Event\Subscription\Factory $factory, Connection $connection, int $maxTransactionSize = 1)
    {
        $this->factory = $factory;
        $this->connection = $connection;

        if ($maxTransactionSize < 1) {
            throw new \InvalidArgumentException(sprintf('Maximum transaction size must be at least "1", but "%d" given.', $maxTransactionSize));
        }

        $this->maxTransactionSize = $maxTransactionSize;
    }

    public function create(Event\Listener $listener) : Event\Subscription
    {
        $subscription = $this->factory->create($listener);
        $subscription = new DbalTransactionalSubscription($subscription, $this->connection, $this->maxTransactionSize);

        return $subscription;
    }
}
