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
        $iterator = new \IteratorIterator($stream);

        parent::__construct($iterator);

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

    public function from(Event $event) : Event\Stream
    {
        throw new \BadMethodCallException('Method not supported.');
    }

    public function to(Event $event) : Event\Stream
    {
        throw new \BadMethodCallException('Method not supported.');
    }

    public function after(Event $event) : Event\Stream
    {
        throw new \BadMethodCallException('Method not supported.');
    }

    public function before(Event $event) : Event\Stream
    {
        throw new \BadMethodCallException('Method not supported.');
    }

    public function limit(int $limit) : Event\Stream
    {
        throw new \BadMethodCallException('Method not supported.');
    }

    public function only(string ...$types) : Event\Stream
    {
        throw new \BadMethodCallException('Method not supported.');
    }

    public function without(string ...$types) : Event\Stream
    {
        throw new \BadMethodCallException('Method not supported.');
    }

    public function first() : ?Event
    {
        throw new \BadMethodCallException('Method not supported.');
    }

    public function last() : ?Event
    {
        throw new \BadMethodCallException('Method not supported.');
    }
}
