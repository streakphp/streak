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

namespace Streak\Application\Saga;

use Streak\Application\Saga;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @method Saga create(Saga\Id $id)
 * @method Saga createFor(Event $event)
 */
interface Factory extends Event\Listener\Factory
{
//    public function create(Saga\Id $id) : Saga;

//    public function createFor(Event $event) : Saga;
}
