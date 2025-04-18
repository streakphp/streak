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

namespace Streak\Infrastructure\Domain\EventStore;

use Streak\Domain\Event;
use Streak\Domain\EventStore;
use Streak\Domain\Exception;
use Streak\Domain\Id;
use Streak\Domain\Id\UUID;
use Streak\Infrastructure\Domain\Event\InMemoryStream;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Domain\EventStore\InMemoryEventStoreTest
 */
class InMemoryEventStore implements EventStore
{
    private array $streams = [];

    /** @var Event\Envelope[] */
    private array $all = [];

    /**
     * @throws Exception\ConcurrentWriteDetected
     * @throws Exception\EventAlreadyInStore
     */
    public function add(Event\Envelope ...$events): array
    {
        if (0 === \count($events)) {
            return [];
        }

        $backup = [
            'all' => $this->all,
            'streams' => $this->streams,
        ];
        foreach ($events as $event) {
            $producerId = $event->producerId();
            $streamName = $this->streamName($producerId);

            foreach ($this->all as $stored) {
                if ($stored->equals($event)) {
                    throw new Exception\EventAlreadyInStore($event);
                }
            }

            if (!isset($this->streams[$streamName])) {
                $this->streams[$streamName] = [];
            }

            $version = $event->version();
            if (null !== $version) {
                if (isset($this->streams[$streamName][$version])) {
                    // rollback
                    $this->all = $backup['all'];
                    $this->streams = $backup['streams'];

                    throw new Exception\ConcurrentWriteDetected($producerId);
                }
            } else {
                $version = \count($this->streams[$streamName]);
                $version += 1;
            }

            $this->streams[$streamName][$version] = $event;
            $this->all[] = $event;
        }

        return $events;
    }

    public function event(UUID $uuid): ?Event\Envelope
    {
        foreach ($this->all as $event) {
            if ($event->uuid()->equals($uuid)) {
                return $event;
            }
        }

        return null;
    }

    public function stream(?EventStore\Filter $filter = null): Event\Stream
    {
        if (null === $filter) {
            $filter = EventStore\Filter::nothing();
        }

        if (0 === \count($filter->producerIds()) && 0 === \count($filter->producerTypes())) {
            return new InMemoryStream(...$this->all);
        }

        $streamNames = [];
        foreach ($filter->producerIds() as $producerId) {
            $streamName = $this->streamName($producerId);

            if (\array_key_exists($streamName, $this->streams)) {
                $streamNames[] = $streamName;
            }
        }

        foreach ($filter->producerTypes() as $producerType) {
            foreach ($this->streams as $type => $stream) {
                if (0 === mb_strpos($type, $producerType)) {
                    $streamNames[] = $type;
                }
            }
        }

        $streams = [];
        foreach ($streamNames as $streamName) {
            $streams = [...$streams, ...$this->streams[$streamName]];
        }

        $events = [];
        foreach ($this->all as $event) {
            if (\in_array($event, $streams, true)) {
                $events[] = $event;
            }
        }

        return new InMemoryStream(...$events);
    }

    public function clear(): void
    {
        $this->streams = [];
        $this->all = [];
    }

    private function streamName(Id $producerId): string
    {
        $type = $producerId::class;
        $id = $producerId->toString();

        return $type.$id;
    }
}
