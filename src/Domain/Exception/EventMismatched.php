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

namespace Streak\Domain\Exception;

use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Domain\Exception\EventMismatchedTest
 */
class EventMismatched extends \LogicException
{
    public function __construct(private object $object, private Event\Envelope $event, \Throwable $previous = null)
    {
        parent::__construct('Event mismatched when applying on object.', 0, $previous);
    }

    public function object(): object
    {
        return $this->object;
    }

    public function event(): Event\Envelope
    {
        return $this->event;
    }
}
