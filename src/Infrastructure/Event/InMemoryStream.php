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

namespace Streak\Infrastructure\Event;

use Streak\Domain\Event;
use Streak\Domain\Event\FilterableStream;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
final class InMemoryStream implements Event\FilterableStream
{
    private $events = [];
    private $current = 0;
    private $count = 0;

    public function __construct(Event ...$events)
    {
        $this->events = $events;
        $this->count = count($events);
    }

    public function from(Event $first) : FilterableStream
    {
        $stream = new self();
        $found = false;
        foreach ($this->events as $event) {
            if ($event === $first) {
                $found = true;
            }

            if (true === $found) {
                $stream->add($event);
            }
        }

        return $stream;
    }

    public function empty() : bool
    {
        return 0 === count($this->events);
    }

    public function to(Event $last) : FilterableStream
    {
        $stream = new self();
        foreach ($this->events as $event) {
            $stream->add($event);

            if ($event === $last) {
                return $stream;
            }
        }

        return $stream;
    }

    public function after(Event $first) : FilterableStream
    {
        $stream = new self();
        $found = false;
        foreach ($this->events as $event) {
            if (true === $found) {
                $stream->add($event);
            }

            if ($event === $first) {
                $found = true;
            }
        }

        return $stream;
    }

    public function before(Event $last) : FilterableStream
    {
        $stream = new self();
        foreach ($this->events as $event) {
            if ($event === $last) {
                return $stream;
            }

            $stream->add($event);
        }

        return $stream;
    }

    public function limit(int $limit) : FilterableStream
    {
        $events = array_slice($this->events, 0, $limit);
        $stream = new self(...$events);

        return $stream;
    }

    public function first() : ?Event
    {
        if ($this->empty()) {
            return null;
        }

        return $this->events[0];
    }

    public function last() : ?Event
    {
        if ($this->empty()) {
            return null;
        }

        return $this->events[$this->count - 1];
    }

    public function current() : Event
    {
        return $this->events[$this->current];
    }

    public function next()
    {
        $this->current = $this->current + 1;
    }

    public function key()
    {
        return $this->current;
    }

    public function valid()
    {
        return array_key_exists($this->current, $this->events);
    }

    public function rewind()
    {
        $this->current = 0;
    }

    private function add(Event $event)
    {
        $this->events[] = $event;
        ++$this->count;
    }
}
