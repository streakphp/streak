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

use Doctrine\DBAL\Driver\Connection;
use Streak\Domain\Event;
use Streak\Domain\Event\Subscription;
use Streak\Infrastructure\Event\Subscription\DbalTransactionalSubscription;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class Factory implements Subscription\Factory
{
    private $factory;
    private $connection;
    private $class;
    private $maxTransactionSize;

    public function __construct(Subscription\Factory $factory, Connection $connection, int $maxTransactionSize = 1)
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
