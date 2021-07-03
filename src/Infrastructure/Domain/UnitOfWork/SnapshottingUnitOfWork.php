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

use InvalidArgumentException;
use Streak\Domain\Event;
use Streak\Infrastructure\Domain\AggregateRoot\Snapshotter;
use Streak\Infrastructure\Domain\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Domain\UnitOfWork\SnapshottingUnitOfWorkTest
 */
class SnapshottingUnitOfWork implements UnitOfWork
{
    private \SplObjectStorage $versions;
    private bool $committing = false;
    private int $interval = 1;

    public function __construct(private UnitOfWork $uow, private Snapshotter $snapshotter, int $interval = 1)
    {
        if ($interval < 1) {
            throw new InvalidArgumentException('Interval must be positive!');
        }
        $this->versions = new \SplObjectStorage();
        $this->interval = $interval;
    }

    public function add(object $producer): void
    {
        $this->uow->add($producer);

        if ($producer instanceof Event\Sourced\AggregateRoot) {
            $id = $producer->producerId();
            $version = $producer->version();
            $this->versions->attach($id, $version);
        }
    }

    public function remove(object $producer): void
    {
        if ($producer instanceof Event\Sourced\AggregateRoot) {
            $id = $producer->producerId();
            $this->versions->offsetUnset($id);
        }

        $this->uow->remove($producer);
    }

    public function has(object $producer): bool
    {
        return $this->uow->has($producer);
    }

    /**
     * @return Event\Producer[]
     */
    public function uncommitted(): array
    {
        return $this->uow->uncommitted();
    }

    public function count(): int
    {
        return $this->uow->count();
    }

    public function commit(): \Generator
    {
        if (false === $this->committing) {
            $this->committing = true;

            try {
                foreach ($this->uow->commit() as $committed) {
                    if (!$committed instanceof Event\Sourced\AggregateRoot) {
                        yield $committed;

                        continue;
                    }

                    $versionBeforeCommit = $this->versions->offsetGet($committed->producerId());
                    $versionAfterCommit = $committed->version();

                    $this->versions->offsetUnset($committed->producerId());

                    if (!$this->isReadyForSnapshot($versionBeforeCommit, $versionAfterCommit)) {
                        yield $committed;

                        continue;
                    }

                    $this->snapshotter->takeSnapshot($committed);

                    yield $committed;
                }

                $this->clear();
            } finally {
                $this->committing = false;
            }
        }
    }

    public function clear(): void
    {
        $this->versions = new \SplObjectStorage(); // clear
        $this->uow->clear();
    }

    private function isReadyForSnapshot(int $before, int $after): bool
    {
        if ((int) ($before / $this->interval) !== (int) ($after / $this->interval)) {
            return true;
        }

        return false;
    }
}
