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
use Streak\Domain\EventSourced;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface AggregateRoot extends Domain\AggregateRoot, EventSourced\Entity
{
}
