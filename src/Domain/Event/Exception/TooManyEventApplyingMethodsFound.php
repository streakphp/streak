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
 * @see \Streak\Domain\Event\Exception\TooManyEventApplyingMethodsFoundTest
 */
class TooManyEventApplyingMethodsFound extends \BadMethodCallException
{
    private Event\Consumer $consumer;
    private Event\Envelope $event;

    public function __construct(Event\Consumer $consumer, Event\Envelope $event, \Throwable $previous = null)
    {
        $this->consumer = $consumer;
        $this->event = $event;

        parent::__construct('Too many event applying methods found.', 0, $previous);
    }

    public function consumer() : Event\Consumer
    {
        return $this->consumer;
    }

    public function event() : Event\Envelope
    {
        return $this->event;
    }
}
