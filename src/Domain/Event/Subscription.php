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

use Streak\Domain\Event;
use Streak\Domain\Event\Subscription\Exception;
use Streak\Domain\EventStore;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * TODO: move under Streak\Domain\Event\Listener
 */
interface Subscription
{
    public function listener(): Event\Listener;

    public function id(): Event\Listener\Id;

    /**
     * @throws Exception\SubscriptionAlreadyCompleted
     * @throws Exception\SubscriptionNotStartedYet
     *
     * @return Event\Envelope[]|iterable
     */
    public function subscribeTo(EventStore $store, ?int $limit = null): iterable;

    public function startFor(Event\Envelope $event): void;

    /**
     * @throws Exception\SubscriptionNotStartedYet
     * @throws Exception\SubscriptionRestartNotPossible
     */
    public function restart(): void;

    public function paused(): bool;

    public function pause(): void;

    public function unpause(): void; // maybe: "play", "resume play" or rework all actions: play/start, stop, resume, rewind/restart

    public function starting(): bool;

    public function started(): bool;

    public function completed(): bool;

    public function version(): int;
}
