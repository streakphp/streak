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

namespace Streak\Application\ProcessManager;

use Streak\Application\ProcessManager;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @method ProcessManager create(ProcessManager\Id $id)
 * @method ProcessManager createFor(Event $event)
 */
interface Factory extends Event\Listener\Factory
{
//    public function create(ProcessManager\Id $id) : ProcessManager;

//    public function createFor(Event $event) : ProcessManager;
}
