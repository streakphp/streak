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

namespace Streak\Domain\Event\Sourced\Subscription;

use Streak\Domain\Event;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionListenedToEvent;

/**
 * Stream that iterates only over SubscriptionListenedToEvent events and emits events that SubscriptionListenedToEvent contains.
 *
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class Stream extends \FilterIterator implements Event\Stream
{
    private $stream;
    private $position = 0;

    public function __construct(Event\Stream $stream)
    {
        parent::__construct($stream);

        $this->stream = $stream;
    }

    public function accept()
    {
        $event = $this->getInnerIterator()->current();

        if ($event instanceof SubscriptionListenedToEvent) {
            return true;
        }

        return false;
    }

    public function next()
    {
        parent::next();
        ++$this->position;
    }

    public function key()
    {
        parent::key();

        return $this->position;
    }

    public function empty() : bool
    {
        return $this->stream->empty();
    }

    public function current() : Event
    {
        $event = $this->getInnerIterator()->current();

        return $event->event();
    }
}
