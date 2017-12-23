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

namespace Streak\Domain\Exception;

use Streak\Domain;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class EventAndConsumerMismatch extends \LogicException
{
    private $consumer;
    private $event;

    public function __construct(Event\Consumer $consumer, Domain\Event $event, \Throwable $previous = null)
    {
        $this->consumer = $consumer;
        $this->event = $event;

        parent::__construct('Event mismatched when applying on consumer.', 0, $previous);
    }

    public function consumer() : Event\Consumer
    {
        return $this->consumer;
    }

    public function event() : Event
    {
        return $this->event;
    }
}
