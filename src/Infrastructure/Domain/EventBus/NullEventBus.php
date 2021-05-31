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

namespace Streak\Infrastructure\Domain\EventBus;

use Streak\Domain\Event;
use Streak\Domain\EventBus;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @codeCoverageIgnore
 *
 * @see \Streak\Infrastructure\Domain\EventBus\NullEventBusTest
 */
class NullEventBus implements EventBus
{
    public function add(\Streak\Application\Event\Listener $listener): void
    {
    }

    public function remove(\Streak\Application\Event\Listener $listener): void
    {
    }

    public function publish(Event\Envelope ...$messages): void
    {
    }
}
