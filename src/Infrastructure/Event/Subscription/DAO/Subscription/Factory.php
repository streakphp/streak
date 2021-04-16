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

namespace Streak\Infrastructure\Event\Subscription\DAO\Subscription;

use Streak\Domain\Clock;
use Streak\Domain\Event;
use Streak\Infrastructure\Event\Subscription\DAO\Subscription;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Event\Subscription\DAO\Subscription\FactoryTest
 */
class Factory implements Event\Subscription\Factory
{
    private Clock $clock;

    public function __construct(Clock $clock)
    {
        $this->clock = $clock;
    }

    public function create(Event\Listener $listener): Event\Subscription
    {
        return new Subscription($listener, $this->clock);
    }
}
