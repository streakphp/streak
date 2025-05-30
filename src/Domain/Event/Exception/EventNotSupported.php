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

namespace Streak\Domain\Event\Exception;

use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Domain\Event\Exception\EventNotSupportedTest
 */
class EventNotSupported extends \InvalidArgumentException
{
    public function __construct(private Event\Envelope $event, \Throwable $previous = null)
    {
        parent::__construct(\sprintf('Event "%s" not supported.', $event->name()), 0, $previous);
    }

    public function event(): Event\Envelope
    {
        return $this->event;
    }
}
