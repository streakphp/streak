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

namespace Streak\Domain\Event\Sourced\Subscription\Event;

use Streak\Domain\Event\Sourced\Subscription;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @codeCoverageIgnore
 */
class SubscriptionCompleted implements Subscription\Event
{
    public function __construct()
    {
        // remove @codeCoverageIgnore if any logic appear here.
    }
}
