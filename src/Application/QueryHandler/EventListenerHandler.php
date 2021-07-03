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

namespace Streak\Application\QueryHandler;

use Streak\Domain\Event\Subscription;
use Streak\Domain\Event\Subscription\Exception\ListenerNotFound;
use Streak\Domain\Exception\QueryNotSupported;
use Streak\Domain\Query;
use Streak\Domain\QueryHandler;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Application\QueryHandler\EventListenerHandlerTest
 */
class EventListenerHandler implements QueryHandler
{
    public function __construct(private Subscription\Repository $repository)
    {
    }

    public function handleQuery(Query $query)
    {
        if (!$query instanceof Query\EventListenerQuery) {
            throw new QueryNotSupported($query);
        }

        $id = $query->listenerId();
        $subscription = $this->repository->find($id);

        if (null === $subscription) {
            throw new ListenerNotFound($id);
        }

        $listener = $subscription->listener();

        if (!$listener instanceof QueryHandler) {
            throw new QueryNotSupported($query);
        }

        return $listener->handleQuery($query);
    }
}
