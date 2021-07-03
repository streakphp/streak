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

namespace Streak\Infrastructure\Application\QueryBus;

use Streak\Application;
use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Application\QueryBus\SynchronousQueryBusTest
 */
class SynchronousQueryBus implements Application\QueryBus
{
    /**
     * @var Domain\QueryHandler[]
     */
    private array $handlers = [];

    public function register(Domain\QueryHandler $handler): void
    {
        $this->handlers[] = $handler;
    }

    /**
     * @throws Domain\Exception\QueryNotSupported
     */
    public function dispatch(Domain\Query $query)
    {
        foreach ($this->handlers as $handler) {
            try {
                return $handler->handleQuery($query);
            } catch (Domain\Exception\QueryNotSupported) {
                continue;
            }
        }

        throw new Domain\Exception\QueryNotSupported($query);
    }
}
