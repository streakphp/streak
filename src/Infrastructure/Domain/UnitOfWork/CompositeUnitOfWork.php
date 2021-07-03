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

use Streak\Infrastructure\Domain\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Domain\UnitOfWork\CompositeUnitOfWorkTest
 */
class CompositeUnitOfWork implements UnitOfWork
{
    /**
     * @var UnitOfWork[]
     */
    private array $uows = [];

    public function __construct(UnitOfWork ...$uows)
    {
        $this->uows = $uows;
    }

    public function add(object $object): void
    {
        foreach ($this->uows as $uow) {
            try {
                $uow->add($object);

                return;
            } catch (Exception\ObjectNotSupported) {
                continue;
            }
        }

        throw new Exception\ObjectNotSupported($object);
    }

    public function remove(object $object): void
    {
        foreach ($this->uows as $uow) {
            $uow->remove($object);
        }
    }

    public function has(object $object): bool
    {
        foreach ($this->uows as $uow) {
            if (true === $uow->has($object)) {
                return true;
            }
        }

        return false;
    }

    public function uncommitted(): array
    {
        $uncommitted = [];
        foreach ($this->uows as $uow) {
            $uncommitted = [...$uncommitted, ...$uow->uncommitted()];
        }

        return $uncommitted;
    }

    public function count(): int
    {
        $count = 0;
        foreach ($this->uows as $uow) {
            $count += $uow->count();
        }

        return $count;
    }

    public function commit(): \Generator
    {
        foreach ($this->uows as $uow) {
            foreach ($uow->commit() as $object) {
                yield $object;
            }
        }
    }

    public function clear(): void
    {
        foreach ($this->uows as $uow) {
            $uow->clear();
        }
    }
}
