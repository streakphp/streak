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

namespace Streak\Infrastructure\Domain\Event\Sourced\Subscription;

use Streak\Domain\Clock;
use Streak\Domain\Event;
use Streak\Domain\Event\Subscription;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Domain\Event\Sourced\Subscription\FactoryTest
 */
class Factory implements Subscription\Factory
{
    private Clock $clock;

    public function __construct(Clock $clock)
    {
        $this->clock = $clock;
    }

    /**
     * @return \Streak\Infrastructure\Domain\Event\Sourced\Subscription
     */
    public function create(Event\Listener $listener): Event\Subscription
    {
        return new \Streak\Infrastructure\Domain\Event\Sourced\Subscription($listener, $this->clock);
    }
}
