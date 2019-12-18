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

namespace Streak\Domain\Event;

use Streak\Domain;
use Streak\Domain\Event\Subscription\Exception;
use Streak\Domain\EventStore;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * TODO: move under Streak\Domain\Event\Listener
 */
interface Subscription
{
    public function listener() : Listener;

    public function subscriptionId() : Listener\Id;

    /**
     * @param EventStore $store
     * @param int|null   $limit
     *
     * @return iterable|Domain\Event[]
     *
     * @throws Exception\SubscriptionAlreadyCompleted
     * @throws Exception\SubscriptionNotStartedYet
     */
    public function subscribeTo(EventStore $store, ?int $limit = null) : iterable;

    /**
     * @param Domain\Event $event
     */
    public function startFor(Domain\Event $event) : void;

    /**
     * @throws Exception\SubscriptionNotStartedYet
     * @throws Exception\SubscriptionRestartNotPossible
     */
    public function restart() : void;

    public function starting() : bool;

    public function started() : bool;

    public function completed() : bool;
}
