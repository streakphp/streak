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

namespace Streak\Domain\Event\Listener;

use Streak\Domain\Event;
use Streak\Domain\Event\Exception\InvalidEventGiven;
use Streak\Domain\Event\Listener;
use Streak\Domain\Exception\InvalidIdGiven;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Factory
{
    /**
     * @throws InvalidIdGiven
     */
    public function create(Listener\Id $id) : Listener;

    /**
     * @throws InvalidEventGiven
     */
    public function createFor(Event\Envelope $event) : Event\Listener;
}
