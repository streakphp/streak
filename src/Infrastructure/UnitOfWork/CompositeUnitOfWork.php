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

use Streak\Infrastructure\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class CompositeUnitOfWork implements UnitOfWork
{
    private $uows = [];

    public function __construct(UnitOfWork ...$uows)
    {
        $this->uows = $uows;
    }

    public function add($object) : void
    {
        foreach ($this->uows as $uow) {
            try {
                $uow->add($object);

                return;
            } catch (Exception\ObjectNotSupported $e) {
                continue;
            }
        }

        throw new Exception\ObjectNotSupported($object);
    }

    public function remove($object) : void
    {
        foreach ($this->uows as $uow) {
            $uow->remove($object);
        }
    }

    public function has($object) : bool
    {
        foreach ($this->uows as $uow) {
            if (true === $uow->has($object)) {
                return true;
            }
        }

        return false;
    }

    public function uncommitted() : array
    {
        $uncommitted = [];
        foreach ($this->uows as $uow) {
            $uncommitted = array_merge($uncommitted, $uow->uncommitted());
        }

        return $uncommitted;
    }

    public function count() : int
    {
        $count = 0;
        foreach ($this->uows as $uow) {
            $count += $uow->count();
        }

        return $count;
    }

    public function commit() : \Generator
    {
        foreach ($this->uows as $uow) {
            foreach ($uow->commit() as $object) {
                yield $object;
            }
        }
    }

    public function clear() : void
    {
        foreach ($this->uows as $uow) {
            $uow->clear();
        }
    }
}
