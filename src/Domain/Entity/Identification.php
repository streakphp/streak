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

namespace Streak\Domain\Entity;

use Streak\Domain;
use Streak\Domain\Entity;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
trait Identification
{
    private $id;

    public function __construct(Entity\Id $id)
    {
        $this->identifyBy($id);
    }

    public function identifyBy(Entity\Id $id) : void
    {
        $this->id = $id;
    }

    public function entityId() : Entity\Id
    {
        return $this->id;
    }

    public function id() : Domain\Id
    {
        return $this->id;
    }
}
