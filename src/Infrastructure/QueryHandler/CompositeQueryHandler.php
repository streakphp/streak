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

namespace Streak\Infrastructure\QueryHandler;

use Streak\Application\Exception;
use Streak\Application\Query;
use Streak\Application\QueryHandler;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\QueryHandler\CompositeQueryHandlerTest
 */
class CompositeQueryHandler implements QueryHandler
{
    /**
     * @var QueryHandler[]
     */
    private array $handlers = [];

    public function __construct(QueryHandler ...$handlers)
    {
        foreach ($handlers as $handler) {
            try {
                $this->registerHandler($handler);
            } catch (Exception\QueryHandlerAlreadyRegistered $e) {
                continue;
            }
        }
    }

    /**
     * @throws Exception\QueryHandlerAlreadyRegistered
     */
    public function registerHandler(QueryHandler $handler) : void
    {
        foreach ($this->handlers as $registered) {
            if ($handler === $registered) {
                throw new Exception\QueryHandlerAlreadyRegistered($handler);
            }
        }

        $this->handlers[] = $handler;
    }

    public function handleQuery(Query $query)
    {
        $last = null;
        foreach ($this->handlers as $handler) {
            try {
                return $handler->handleQuery($query);
            } catch (Exception\QueryNotSupported $current) {
                $last = new Exception\QueryNotSupported($query, $current);
            }
        }

        throw new Exception\QueryNotSupported($query, $last);
    }
}
