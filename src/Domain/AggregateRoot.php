<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
abstract class AggregateRoot implements Entity
{
    private $id;

    public function __construct(AggregateRoot\Id $id)
    {
        $this->id = $id;
    }

    final public function id() : Id
    {
        return $this->id;
    }

    final public function equals(Entity $aggregate) : bool
    {
        if (!$aggregate instanceof static) {
            return false;
        }

        return $this->id()->equals($aggregate->id());
    }
}
