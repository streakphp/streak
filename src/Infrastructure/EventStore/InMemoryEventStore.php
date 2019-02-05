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
class InMemoryEventStore implements EventStore
{
    private $factory;

    private $uuids = [];
    private $streams = [];
    private $all = [];

    private $current = 0;

    public function __construct(Domain\Id\Uuid\Uuid4Factory $factory)
    {
        $this->factory = $factory;
    }

    public function producerId(Event $event) : Domain\Id
    {
        $metadata = Event\Metadata::fromObject($event);

        if ($metadata->empty()) {
            throw new Exception\EventNotInStore($event);
        }

        $producerType = $metadata->get('producer_type');
        $producerId = $metadata->get('producer_id');

        $reflection = new \ReflectionClass($producerType);

        if (!$reflection->implementsInterface(Domain\Id::class)) {
            throw new \InvalidArgumentException(); // TODO: domain exception here
        }

        $method = $reflection->getMethod('fromString');

        return $method->invoke(null, $producerId);
    }

    /**
     * @throws Exception\ConcurrentWriteDetected
     * @throws Exception\EventAlreadyInStore
     */
    public function add(Domain\Id $producerId, ?int $version, Event ...$events) : void
    {
        if (0 === count($events)) {
            return;
        }

        $type = get_class($producerId);
        $id = $producerId->toString();
        $stream = $type.$id;

        $transaction = [
            'uuids' => [],
            'all' => [],
            'stream' => [],
        ];
        foreach ($events as $event) {
            $metadata = Event\Metadata::fromObject($event);

            if (!$metadata->empty()) {
                throw new Exception\EventAlreadyInStore($event);
            }

            $uuid = $this->factory->generateUuid4()->toString();

            if (!isset($this->streams[$stream])) {
                $this->streams[$stream] = [];
            }

            if (null === $version) { // no versioning
                $version = count($this->streams[$stream]);
            } else {
                ++$version;
                if (isset($this->streams[$stream][$version])) {
                    throw new Exception\ConcurrentWriteDetected($producerId);
                }
            }

            $metadata->set('uuid', $uuid);
            $metadata->set('producer_type', get_class($producerId));
            $metadata->set('producer_id', $producerId->toString());

            $transaction['uuids'][] = $uuid;
            $transaction['stream'][$version] = $event;
            $transaction['all'][] = $event;
            $transaction['metadata'][] = [$event, $metadata];
        }

        $this->uuids = array_merge($this->uuids, $transaction['uuids']);
        $this->streams[$stream] = $this->streams[$stream] + $transaction['stream'];
        $this->all = array_merge($this->all, $transaction['all']);

        foreach ($transaction['metadata'] as $pair) {
            $event = $pair[0];
            $metadata = $pair[1];
            /* @var $metadata Event\Metadata */
            $metadata->toObject($event);
        }
    }

    public function stream(?EventStore\Filter $filter = null) : Event\Stream
    {
        if (null === $filter) {
            $filter = EventStore\Filter::nothing();
        }

        if (0 === count($filter->producerIds()) && 0 === count($filter->producerTypes())) {
            return new InMemoryStream(...$this->all);
        }

        $keys = [];
        foreach ($filter->producerIds() as $producerId) {
            $producerId = get_class($producerId).$producerId->toString();

            if (array_key_exists($producerId, $this->streams)) {
                $keys[] = $producerId;
            }
        }

        foreach ($filter->producerTypes() as $producerType) {
            foreach ($this->streams as $type => $stream) {
                if (0 === mb_strpos($type, $producerType)) {
                    $keys[] = $type;
                }
            }
        }

        $streams = [];
        foreach ($keys as $key) {
            $streams = array_merge($streams, $this->streams[$key]);
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
}
