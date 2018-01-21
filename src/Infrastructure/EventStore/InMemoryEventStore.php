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

namespace Streak\Infrastructure\EventStore;

use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\EventStore;
use Streak\Domain\Exception;
use Streak\Infrastructure\Event\InMemoryStream;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class InMemoryEventStore implements EventStore, Event\Log
{
    private $uuids = [];
    private $streams = [];
    private $all = [];

    private $current = 0;

    /**
     * @throws Exception\ConcurrentWriteDetected
     * @throws Exception\EventAlreadyInStore
     */
    public function add(Domain\Id $producerId, ?Event $last = null, Event ...$events) : void
    {
        if (0 === count($events)) {
            return;
        }

        $id = $producerId->toString();

        if (null !== $last) {
            $metadata = Event\Metadata::fromObject($last);

            if (!$metadata->has('version')) {
                throw new Exception\EventNotInStore($last);
            }

            $version = $metadata->get('version', '0');
            $version = (int) $version;
        } else {
            $version = 0;
        }

        $transaction = [
            'uuids' => [],
            'all' => [],
            'stream' => [],
        ];
        foreach ($events as $event) {
            ++$version;
            $metadata = Event\Metadata::fromObject($event);

            if (!$metadata->has('uuid')) {
                $metadata->set('uuid', Domain\Id\UUID::create()->toString());
            }

            $uuid = $metadata->get('uuid');

            if (!isset($this->streams[$id])) {
                $this->streams[$id] = [];
            }

            if (isset($this->streams[$id][$version])) {
                throw new Exception\ConcurrentWriteDetected($producerId);
            }

            $metadata->set('producer_type', get_class($producerId));
            $metadata->set('producer_id', $producerId->toString());
            $metadata->set('version', (string) $version);

            if (in_array($uuid, $this->uuids, true)) {
                throw new Exception\EventAlreadyInStore($event);
            }

            $transaction['uuids'][] = $uuid;
            $transaction['stream'][$version] = $event;
            $transaction['all'][] = $event;

            $metadata->toObject($event);
        }

        $this->uuids = array_merge($this->uuids, $transaction['uuids']);
        $this->streams[$id] = array_merge($this->streams[$id], $transaction['stream']);
        $this->all = array_merge($this->all, $transaction['all']);
    }

    public function streamFor(Domain\Id ...$producers) : Event\FilterableStream
    {
        $streams = [];
        foreach ($producers as $producer) {
            $producer = $producer->toString();
            if (isset($this->streams[$producer])) {
                $streams = array_merge($streams, $this->streams[$producer]);
            }
        }

        $events = [];
        foreach ($this->all as $event) {
            if (in_array($event, $streams, true)) {
                $events[] = $event;
            }
        }

        return new InMemoryStream(...$events);
    }

    public function stream(Domain\Id ...$producers) : Event\FilterableStream
    {
        return $this->streamFor(...$producers);
    }

    public function clear()
    {
        $this->streams = [];
        $this->all = [];
    }

    public function current() : Event
    {
        return $this->all[$this->current];
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
        return array_key_exists($this->current, $this->all);
    }

    public function rewind()
    {
        $this->current = 0;
    }

    public function log() : Event\Log
    {
        return $this;
    }
}
