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

namespace Streak\Infrastructure;

use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Exception\ConcurrentWriteDetected;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class UnitOfWork
{
    /**
     * @var Domain\EventStore
     */
    private $store;

    /**
     * @var array[]
     */
    private $producers = [];

    private $committing = false;

    public function __construct(Domain\EventStore $store)
    {
        $this->store = $store;
        $this->producers = [];
    }

    public function add(Event\Producer $producer) : void
    {
        if (!$this->has($producer)) {
            $version = null;
            if ($producer instanceof Domain\Versionable) {
                $version = $producer->version();
            }
            $this->producers[] = [$producer, $version];
        }
    }

    public function remove(Event\Producer $producer) : void
    {
        foreach ($this->producers as $key => [$current, $last]) {
            /* @var $current Event\Producer */
            if ($current->producerId()->equals($producer->producerId())) {
                unset($this->producers[$key]);

                return;
            }
        }
    }

    public function has(Event\Producer $producer) : bool
    {
        foreach ($this->producers as $key => [$current, $last]) {
            /* @var $current Event\Producer */
            if ($current->producerId()->equals($producer->producerId())) {
                return true;
            }
        }

        return false;
    }

    public function count() : int
    {
        return count($this->producers);
    }

    public function commit() : void
    {
        if (false === $this->committing) {
            $this->committing = true;

            try {
                while ($pair = array_shift($this->producers)) {
                    [$producer, $version] = $pair;

                    try {
                        $producerId = $producer->producerId();
                        $events = $producer->events();

                        $this->store->add($producerId, $version, ...$events);

                        if ($producer instanceof Domain\Versionable) {
                            $producer->commit();
                        }
                    } catch (ConcurrentWriteDetected $e) {
                        // version must be wrong so nothing good if we retry it later on...
                        throw $e;
                    } catch (\Exception $e) {
                        // something unexpected occurred, so lets leave uow in state from just before it happened - we may like to retry it later...
                        array_unshift($this->producers, [$producer, $version]);
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
        $this->producers = [];
    }
}
