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

namespace Streak\Infrastructure\UnitOfWork;

use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Exception\ConcurrentWriteDetected;
use Streak\Infrastructure\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class EventStoreUnitOfWork implements UnitOfWork
{
    /**
     * @var Domain\EventStore
     */
    private $store;

    /**
     * @var Event\Producer[]
     */
    private $uncommited = [];

    private $committing = false;

    public function __construct(Domain\EventStore $store)
    {
        $this->store = $store;
        $this->uncommited = [];
    }

    public function add(Event\Producer $producer) : void
    {
        if (!$this->has($producer)) {
            $this->uncommited[] = $producer;
        }
    }

    public function remove(Event\Producer $producer) : void
    {
        foreach ($this->uncommited as $key => $current) {
            /* @var $current Event\Producer */
            if ($current->producerId()->equals($producer->producerId())) {
                unset($this->uncommited[$key]);

                return;
            }
        }
    }

    public function has(Event\Producer $producer) : bool
    {
        foreach ($this->uncommited as $current) {
            /* @var $current Event\Producer */
            if ($current->producerId()->equals($producer->producerId())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Event\Producer[]
     */
    public function uncommitted() : array
    {
        return array_values($this->uncommited);
    }

    public function count() : int
    {
        return count($this->uncommited);
    }

    public function commit() : \Generator
    {
        if (false === $this->committing) {
            $this->committing = true;

            try {
                /** @var $producer Event\Producer */
                while ($producer = array_shift($this->uncommited)) {
                    try {
                        $this->store->add(...$producer->events()); // maybe gather all events and send them in one single EventStore:add() call?

                        if ($producer instanceof Domain\Versionable) {
                            $producer->commit();
                        }

                        yield $producer;
                    } catch (ConcurrentWriteDetected $e) {
                        // version must be wrong so nothing good if we retry it later on...
                        throw $e;
                    } catch (\Exception $e) {
                        // something unexpected occurred, so lets leave uow in state from just before it happened - we may like to retry it later...
                        array_unshift($this->uncommited, $producer);
                        throw $e;
                    }
                }

                $this->clear();
            } finally {
                $this->committing = false;
            }
        }
    }

    public function clear() : void
    {
        $this->uncommited = [];
    }
}
