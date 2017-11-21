<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Domain\EventSourced\Exception;

use Domain;
use Domain\EventSourced;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class MoreThanOneEventApplyingMethodFound extends \BadMethodCallException
{
    /**
     * @var EventSourced\AggregateRoot
     */
    private $aggregate;

    /**
     * @var Domain\Event
     */
    private $event;

    public function __construct(EventSourced\AggregateRoot $aggregate, Domain\Event $event, \Throwable $previous = null)
    {
        $this->aggregate = $aggregate;
        $this->event     = $event;

        $message = sprintf('Found more than one method on "%s" aggregate root to apply an "%s" event.', get_class($aggregate), get_class($event));

        parent::__construct($message, 0, $previous);
    }

    public function getAggregateRoot() : EventSourced\AggregateRoot
    {
        return $this->aggregate;
    }

    public function getEvent() : Domain\Event
    {
        return $this->event;
    }
}
