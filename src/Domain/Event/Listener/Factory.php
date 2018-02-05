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

use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Event\Exception;
use Streak\Domain\Event\Listener;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Factory
{
    /**
     * @throws Domain\Exception\InvalidIdGiven
     */
    public function create(Domain\Id $id) : Listener;

    /**
     * @throws Exception\InvalidEventGiven
     */
    public function createFor(Event $event) : Listener;
}
