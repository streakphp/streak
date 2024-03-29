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

use Streak\Domain\Aggregate;
use Streak\Domain\Entity;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Domain\EventSourcingTest
 */
trait EventSourcing //implements Event\Sourced\Aggregate
{
    use Entity\EventSourcing;

    abstract public function id(): Aggregate\Id;
}
