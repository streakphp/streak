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

namespace Streak\Domain\Event\Subscription;

use Streak\Domain\Event;

/**
 * @template T of Event\Subscription
 *
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Factory
{
    /**
     * @return T
     */
    public function create(Event\Listener $listener): Event\Subscription;
}
