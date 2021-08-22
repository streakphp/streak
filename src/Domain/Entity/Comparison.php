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
 *
 * @see \Streak\Domain\Entity\ComparisonTest
 */
trait Comparison
{
    abstract public function id(): Entity\Id;

    final public function equals(object $entity): bool
    {
        if (!$this instanceof Domain\Entity) {
            return false;
        }

        if (!$entity instanceof self) {
            return false;
        }

        if (!$this->id()->equals($entity->id())) {
            return false;
        }

        return true;
    }
}
