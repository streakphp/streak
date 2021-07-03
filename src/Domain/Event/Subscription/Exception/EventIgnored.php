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

use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Domain\Event\Subscription\Exception\EventIgnoredTest
 */
class EventIgnored extends \RuntimeException
{
    public function __construct(private Event\Envelope $event)
    {
        $message = sprintf('Event "%s" was ignored.', $event->message()::class);

        parent::__construct($message);
    }

    public function event(): Event\Envelope
    {
        return $this->event;
    }
}
