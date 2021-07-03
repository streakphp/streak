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

namespace Streak\Domain\Event\Subscription\Exception;

use Streak\Domain\Event\Subscription;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Domain\Event\Subscription\Exception\SubscriptionPausedTest
 */
class SubscriptionPaused extends \RuntimeException implements Subscription\Exception
{
    public function __construct(private Subscription $subscription)
    {
        $message = sprintf('Subscription "%s#%s" is paused.', $this->subscription->subscriptionId()::class, $this->subscription->subscriptionId()->toString());

        parent::__construct($message);
    }

    public function subscription(): Subscription
    {
        return $this->subscription;
    }
}
