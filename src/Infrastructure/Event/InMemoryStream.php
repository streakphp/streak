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
 *
 * @see \Streak\Infrastructure\Event\InMemoryStreamTest
 */
final class InMemoryStream implements \IteratorAggregate, Event\Stream
{
    /**
     * @var Event\Envelope[]
     */
    private array $events = [];

    private array $only = [];
    private array $without = [];
    private ?Event\Envelope $from = null;
    private ?Event\Envelope $to = null;
    private ?Event\Envelope $after = null;
    private ?Event\Envelope $before = null;
    private ?int $limit = null;

    public function __construct(Event\Envelope ...$events)
    {
        $this->events = $events;
        $this->events = array_values($this->events); // reset keys
    }

    public function from(Event\Envelope $event): Stream
    {
        $stream = $this->copy();
        $stream->from = $event;
        $stream->after = null;

        return $stream;
    }

    public function count(): int
    {
        $events = $this->filter();

        return \count($events);
    }

    public function empty(): bool
    {
        return 0 === $this->count();
    }

    public function to(Event\Envelope $event): Stream
    {
        $stream = $this->copy();
        $stream->to = $event;
        $stream->before = null;

        return $stream;
    }

    public function after(Event\Envelope $event): Stream
    {
        $stream = $this->copy();
        $stream->from = null;
        $stream->after = $event;

        return $stream;
    }

    public function before(Event\Envelope $event): Stream
    {
        $stream = $this->copy();
        $stream->to = null;
        $stream->before = $event;

        return $stream;
    }

    public function only(string ...$types): Stream
    {
        $stream = $this->copy();
        $stream->only = $types;
        $stream->without = [];

        // TODO: check if type is Domain\Id

        return $stream;
    }

    public function without(string ...$types): Stream
    {
        $stream = $this->copy();
        $stream->without = $types;
        $stream->only = [];

        // TODO: check if type is Domain\Id

        return $stream;
    }

    public function limit(int $limit): Stream
    {
        $stream = $this->copy();
        $stream->limit = $limit;

        return $stream;
    }

    public function first(): ?Event\Envelope
    {
        $events = $this->filter();

        return array_shift($events);
    }

    public function last(): ?Event\Envelope
    {
        $events = $this->filter();

        return array_pop($events);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->filter());
    }

    private function copy(): self
    {
        $stream = new self(...$this->events);
        $stream->from = $this->from;
        $stream->to = $this->to;
        $stream->after = $this->after;
        $stream->before = $this->before;
        $stream->limit = $this->limit;
        $stream->only = $this->only;
        $stream->without = $this->without;

        return $stream;
    }

    private function search(Event\Envelope $event): ?int
    {
        foreach ($this->events as $key => $stored) {
            if ($stored->uuid()->equals($event->uuid())) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @return Event[]
     */
    private function filter(): array
    {
        if (0 === \count($this->events)) {
            return [];
        }

        $start = 0;

        if ($this->from) {
            $index = $this->search($this->from);

            if (null !== $index) {
                $start = $index;
            }
        }

        if ($this->after) {
            $index = $this->search($this->after);

            if (null !== $index) {
                $start = $index + 1;
            }
        }

        $stop = \count($this->events) - 1;

        if ($this->to) {
            $index = $this->search($this->to);

            if (null !== $index) {
                $stop = $index;
            }
        }

        if ($this->before) {
            $index = $this->search($this->before);

            if (null !== $index) {
                $stop = $index - 1;
            }
        }

        $events = [];
        for ($current = $start; $current <= $stop; ++$current) {
            $event = $this->events[$current];

            if ($this->only) {
                $type = $event->name();

                if (!\in_array($type, $this->only, true)) {
                    continue;
                }
            }

            if ($this->without) {
                $type = $event->name();

                if (\in_array($type, $this->without, true)) {
                    continue;
                }
            }

            $events[] = $event;
        }

        if ($this->limit) {
            $events = \array_slice($events, 0, $this->limit);
        }

        return $events;
    }
}
