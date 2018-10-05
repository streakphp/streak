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
use Streak\Domain\Event\Stream;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
final class InMemoryStream implements \IteratorAggregate, Event\Stream
{
    private $events = [];

    private $including = [];
    private $excluding = [];
    private $from;
    private $to;
    private $after;
    private $before;
    private $limit;

    public function __construct(Event ...$events)
    {
        $this->events = $events;
        $this->events = array_values($this->events); // reset keys
    }

    public function from(Event $event) : Stream
    {
        $stream = $this->copy();
        $stream->from = $event;
        $stream->after = null;

        return $stream;
    }

    public function count() : int
    {
        $events = $this->filter();

        return count($events);
    }

    public function empty() : bool
    {
        return 0 === $this->count();
    }

    public function to(Event $event) : Stream
    {
        $stream = $this->copy();
        $stream->to = $event;
        $stream->before = null;

        return $stream;
    }

    public function after(Event $event) : Stream
    {
        $stream = $this->copy();
        $stream->from = null;
        $stream->after = $event;

        return $stream;
    }

    public function before(Event $event) : Stream
    {
        $stream = $this->copy();
        $stream->to = null;
        $stream->before = $event;

        return $stream;
    }

    public function only(string ...$types) : Stream
    {
        $stream = $this->copy();
        $stream->including = $types;
        $stream->excluding = [];

        // TODO: check if type is Domain\Id

        return $stream;
    }

    public function without(string ...$types) : Stream
    {
        $stream = $this->copy();
        $stream->excluding = $types;
        $stream->including = [];

        // TODO: check if type is Domain\Id

        return $stream;
    }

    public function limit(int $limit) : Stream
    {
        $stream = $this->copy();
        $stream->limit = $limit;

        return $stream;
    }

    public function first() : ?Event
    {
        $events = $this->filter();

        return array_shift($events);
    }

    public function last() : ?Event
    {
        $events = $this->filter();

        return array_pop($events);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->filter());
    }

    private function copy() : self
    {
        $stream = new self(...$this->events);
        $stream->from = $this->from;
        $stream->to = $this->to;
        $stream->after = $this->after;
        $stream->before = $this->before;
        $stream->limit = $this->limit;
        $stream->including = $this->including;
        $stream->excluding = $this->excluding;

        return $stream;
    }

    /**
     * @return Event[]
     */
    private function filter() : array
    {
        if (0 === count($this->events)) {
            return [];
        }

        $start = 0;

        if ($this->from) {
            $index = array_search($this->from, $this->events, true);

            if (false !== $index) {
                $start = $index;
            }
        }

        if ($this->after) {
            $index = array_search($this->after, $this->events, true);

            if (false !== $index) {
                $start = $index + 1;
            }
        }

        $stop = count($this->events) - 1;

        if ($this->to) {
            $index = array_search($this->to, $this->events, true);

            if (false !== $index) {
                $stop = $index;
            }
        }

        if ($this->before) {
            $index = array_search($this->before, $this->events, true);

            if (false !== $index) {
                $stop = $index - 1;
            }
        }

        $events = [];
        for ($current = $start; $current <= $stop; ++$current) {
            $event = $this->events[$current];

            if ($this->including) {
                $type = get_class($event);

                if (!in_array($type, $this->including, true)) {
                    continue;
                }
            }

            if ($this->excluding) {
                $type = get_class($event);

                if (in_array($type, $this->excluding, true)) {
                    continue;
                }
            }

            $events[] = $event;
        }

        if ($this->limit) {
            $events = array_slice($events, 0, $this->limit);
        }

        return $events;
    }
}
