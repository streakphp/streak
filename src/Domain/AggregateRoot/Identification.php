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

use Streak\Domain\AggregateRoot;
use Streak\Domain\Aggregate;
use Streak\Domain\Entity;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
trait Identification
{
    /**
     * @var AggregateRoot\Id
     */
    private $id;

    public function __construct(AggregateRoot\Id $id)
    {
        $this->initialize($id);
    }

    public function initialize(AggregateRoot\Id $id) : void
    {
        $this->id = $id;
    }

    public function aggregateRootId() : AggregateRoot\Id
    {
        return $this->id;
    }

    public function aggregateId() : Aggregate\Id
    {
        return $this->id;
    }

    public function id() : Entity\Id
    {
        return $this->id;
    }
}
