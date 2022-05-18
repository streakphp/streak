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

namespace Streak\Infrastructure\Domain\Event\Subscription\DAO\Subscription;

use Streak\Domain\Clock;
use Streak\Domain\Event;
use Streak\Infrastructure\Domain\Event\Subscription\DAO;

/**
 * @implements Event\Subscription\Factory<DAO\Subscription>
 *
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Domain\Event\Subscription\DAO\Subscription\FactoryTest
 */
class Factory implements Event\Subscription\Factory
{
    public function __construct(private Clock $clock)
    {
    }

    public function create(Event\Listener $listener): DAO\Subscription
    {
        return new DAO\Subscription($listener, $this->clock);
    }
}
