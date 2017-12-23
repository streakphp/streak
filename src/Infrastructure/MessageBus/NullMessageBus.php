<?php

declare(strict_types=1);

/**
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Infrastructure\MessageBus;

use Streak\Domain;
use Streak\Domain\Message;
use Streak\Domain\MessageBus;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class NullMessageBus implements MessageBus
{
    public function subscribe(Message\Subscriber $subscriber) : void
    {
    }

    public function publish(Domain\Message ...$messages)
    {
    }
}
