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

namespace Streak\Domain\Aggregate;

use Streak\Domain;
use Streak\Domain\Aggregate;
use Streak\Domain\Entity;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Domain\Aggregate\ComparisonTest
 */
trait Comparison
{
    use Entity\Comparison;

    abstract public function id(): Aggregate\Id;

    final public function equals(object $aggregate): bool
    {
        if (!$this instanceof Domain\Aggregate) {
            return false;
        }

        if (!$aggregate instanceof self) {
            return false;
        }

        if (!$this->id()->equals($aggregate->id())) {
            return false;
        }

        return true;
    }
}
