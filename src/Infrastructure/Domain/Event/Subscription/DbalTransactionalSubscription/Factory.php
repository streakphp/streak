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

namespace Streak\Infrastructure\Domain\Event\Subscription\DbalTransactionalSubscription;

use Doctrine\DBAL\Driver\Connection;
use Streak\Domain\Event;
use Streak\Infrastructure\Domain\Event\Subscription\DbalTransactionalSubscription;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Domain\Event\Subscription\DbalTransactionalSubscription\FactoryTest
 */
class Factory implements Event\Subscription\Factory
{
    private int $maxTransactionSize;

    public function __construct(private Event\Subscription\Factory $factory, private Connection $connection, int $maxTransactionSize = 1)
    {
        if ($maxTransactionSize < 1) {
            throw new \InvalidArgumentException(sprintf('Maximum transaction size must be at least "1", but "%d" given.', $maxTransactionSize));
        }

        $this->maxTransactionSize = $maxTransactionSize;
    }

    public function create(Event\Listener $listener): Event\Subscription
    {
        $subscription = $this->factory->create($listener);

        return new DbalTransactionalSubscription($subscription, $this->connection, $this->maxTransactionSize);
    }
}
