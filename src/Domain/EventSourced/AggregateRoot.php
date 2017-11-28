<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain\EventSourced;

use Streak\Domain;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
abstract class AggregateRoot implements Domain\AggregateRoot, Domain\Event\Sourced
{
    use Event\Sourcing;

    private $id;

    public function __construct(Domain\AggregateRootId $id)
    {
        $this->id = $id;
    }

    final public function id() : Domain\Id
    {
        return $this->id;
    }

    final public function equals(Domain\Entity $aggregate) : bool
    {
        if (!$aggregate instanceof static) {
            return false;
        }

        return $this->id()->equals($aggregate->id());
    }
}
