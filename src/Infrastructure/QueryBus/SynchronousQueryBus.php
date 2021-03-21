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

namespace Streak\Infrastructure\QueryBus;

use Streak\Application;
use Streak\Application\Exception;
use Streak\Application\Query;
use Streak\Application\QueryHandler;
use Streak\Infrastructure\QueryHandler\CompositeQueryHandler;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\QueryBus\SynchronousQueryBusTest
 */
class SynchronousQueryBus implements Application\QueryBus
{
    private CompositeQueryHandler $handler;

    public function __construct()
    {
        $this->handler = new CompositeQueryHandler();
    }

    /**
     * @throws Exception\QueryHandlerAlreadyRegistered
     */
    public function register(QueryHandler $handler) : void
    {
        $this->handler->registerHandler($handler);
    }

    /**
     * @throws Exception\QueryNotSupported
     */
    public function dispatch(Query $query)
    {
        try {
            return $this->handler->handleQuery($query);
        } catch (Exception\QueryNotSupported $previous) {
            throw new Exception\QueryNotSupported($query, $previous);
        }
    }
}
