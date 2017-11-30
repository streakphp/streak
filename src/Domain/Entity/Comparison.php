<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain\Entity;

use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
trait Comparison
{
    public function equals(Domain\Entity $entity) : bool
    {
        if (!$entity instanceof self) {
            return false;
        }

        if (!$entity->id()->equals($entity->id())) {
            return false;
        }

        return true;
    }
}
