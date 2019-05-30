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

use Streak\Application\Exception\QueryNotSupported;
use Streak\Application\Query;
use Streak\Application\QueryHandler;
use Streak\Domain\Event\Subscription;
use Streak\Domain\Event\Subscription\Exception\ListenerNotFound;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class EventListenerHandler implements QueryHandler
{
    private $repository;

    public function __construct(Subscription\Repository $repository)
    {
        $this->repository = $repository;
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
