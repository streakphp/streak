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

use Streak\Domain\Event;
use Streak\Domain\EventStore;
use Streak\Domain\Exception;
use Streak\Infrastructure\Event\InMemoryStream;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class InMemoryEventStore implements EventStore
{
    private $streams = [];
    private $all = [];

    /**
     * @throws Exception\ConcurrentWriteDetected
     * @throws Exception\EventAlreadyInStore
     */
    public function add(Event\Envelope ...$events) : void
    {
        if (0 === count($events)) {
            return;
        }

        $transaction = [
            'all' => [],
            'stream' => [],
        ];
        foreach ($events as $event) {
            $producerId = $event->producerId();
            $streamName = $this->streamName($producerId);

            $metadata = Event\Metadata::fromObject($event);

            if (!$metadata->empty()) {
                throw new Exception\EventAlreadyInStore($event);
            }

            if (!isset($this->streams[$streamName])) {
                $this->streams[$streamName] = [];
            }

            $version = $event->version();
            if (null !== $version) {
                $version = count($this->streams[$streamName]);

                if (isset($this->streams[$streamName][$version])) {
                    throw new Exception\ConcurrentWriteDetected($producerId);
                }
            }

            $transaction['stream'][$version] = $event;
            $transaction['all'][] = $event;
        }

        // commit
        $this->streams[$streamName] = $this->streams[$streamName] + $transaction['stream'];
        $this->all = array_merge($this->all, $transaction['all']);
    }

    public function stream(?EventStore\Filter $filter = null) : Event\Stream
    {
        if (null === $filter) {
            $filter = EventStore\Filter::nothing();
        }

        if (0 === count($filter->producerIds()) && 0 === count($filter->producerTypes())) {
            return new InMemoryStream(...$this->all);
        }

        $streamNames = [];
        foreach ($filter->producerIds() as $producerId) {
            $streamName = $this->streamName($producerId);

            if (array_key_exists($streamName, $this->streams)) {
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
            $streams = array_merge($streams, $this->streams[$streamName]);
        }

        $events = [];
        foreach ($this->all as $event) {
            if (in_array($event, $streams, true)) {
                $events[] = $event;
            }
        }

        return new InMemoryStream(...$events);
    }

    public function clear()
    {
        $this->streams = [];
        $this->all = [];
    }

    private function streamName(Event\Producer\Id $producerId) : string
    {
        $type = get_class($producerId);
        $id = $producerId->toString();
        $name = $type.$id;

        return $name;
    }
}
