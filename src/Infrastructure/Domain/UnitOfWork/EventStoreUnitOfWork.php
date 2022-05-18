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

namespace Streak\Infrastructure\Domain\UnitOfWork;

use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Exception\ConcurrentWriteDetected;
use Streak\Infrastructure\Domain\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @TODO: rename to EventSourcedUnitOfWork
 *
 * @template-implements UnitOfWork<Event\Producer>
 *
 * @see \Streak\Infrastructure\Domain\UnitOfWork\EventStoreUnitOfWorkTest
 */
class EventStoreUnitOfWork implements UnitOfWork
{
    /**
     * @var Event\Producer[]
     */
    private array $uncommited = [];

    private bool $committing = false;

    public function __construct(private Domain\EventStore $store)
    {
    }

    public function add(object $object): void
    {
        if (!$object instanceof Event\Producer) {
            throw new Exception\ObjectNotSupported($object);
        }

        if (!$this->has($object)) {
            $this->uncommited[] = $object;
        }
    }

    public function remove(object $object): void
    {
        if (!$object instanceof Event\Producer) {
            return;
        }

        foreach ($this->uncommited as $key => $current) {
            // @var $current Event\Producer
            if ($current->id()->equals($object->id())) {
                unset($this->uncommited[$key]);

                return;
            }
        }
    }

    public function has(object $object): bool
    {
        if (!$object instanceof Event\Producer) {
            return false;
        }

        foreach ($this->uncommited as $current) {
            // @var $current Event\Producer
            if ($current->id()->equals($object->id())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Event\Producer[]
     */
    public function uncommitted(): array
    {
        return array_values($this->uncommited);
    }

    public function count(): int
    {
        return \count($this->uncommited);
    }

    /**
     * @throws ConcurrentWriteDetected
     * @throws \Exception
     */
    public function commit(): \Generator
    {
        if (false === $this->committing) {
            $this->committing = true;

            try {
                while ($object = array_shift($this->uncommited)) {
                    /** @var Event\Producer $object */
                    try {
                        $this->store->add(...$object->events()); // maybe gather all events and send them in one single EventStore:add() call?

                        if ($object instanceof Domain\Versionable) {
                            $object->commit();
                        }

                        yield $object;
                    } catch (ConcurrentWriteDetected $e) {
                        // version must be wrong so nothing good if we retry it later on...
                        throw $e;
                    } catch (\Exception $e) {
                        // something unexpected occurred, so lets leave uow in state from just before it happened - we may like to retry it later...
                        array_unshift($this->uncommited, $object);

                        throw $e;
                    }
                }

                $this->clear();
            } finally {
                $this->committing = false;
            }
        }
    }

    public function clear(): void
    {
        $this->uncommited = [];
    }
}
