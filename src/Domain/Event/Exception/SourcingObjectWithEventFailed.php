<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain\Event\Exception;

use Streak\Domain;
use Streak\Domain\EventSourced;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class SourcingObjectWithEventFailed extends \BadMethodCallException
{
    /**
     * @var object
     */
    private $subject;

    /**
     * @var Domain\Event
     */
    private $event;

    public function __construct($object, Domain\Event $event, \Throwable $previous = null)
    {
        if (false === is_object($object)) {
            $message = sprintf('Object expected, but got "%s"', \gettype($object));
            throw new \InvalidArgumentException($message);
        }

        $this->subject = $object;
        $this->event = $event;

        $message = sprintf('Sourcing "%s" object with "%s" event failed.', \get_class($object), \get_class($event));

        parent::__construct($message, 0, $previous);
    }

    public function subject()
    {
        return $this->subject;
    }

    public function event() : Domain\Event
    {
        return $this->event;
    }
}
