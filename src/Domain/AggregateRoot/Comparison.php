<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain\AggregateRoot;

use Streak\Domain;
use Streak\Domain\Aggregate;
use Streak\Domain\AggregateRoot;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
trait Comparison
{
    use Aggregate\Comparison;

    abstract public function aggregateRootId() : AggregateRoot\Id;

    final public function equals($root) : bool
    {
        if (!$this instanceof Domain\AggregateRoot) {
            return false;
        }

        if (!$root instanceof self) {
            return false;
        }

        if (!$this->aggregateRootId()->equals($root->aggregateRootId())) {
            return false;
        }

        return true;
    }
}
