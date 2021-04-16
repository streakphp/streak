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

namespace Streak\Infrastructure\Event;

use Streak\Domain\Event;
use Streak\Domain\Event\Listener;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Event\NullListenerTest
 */
class NullListener implements Event\Listener
{
    use Listener\Identifying;

    public function on(Event\Envelope $event): bool
    {
        return true;
    }

    public static function from(Event\Listener $listener)
    {
        return new self($listener->listenerId());
    }
}
