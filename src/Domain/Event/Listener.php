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

namespace Streak\Domain\Event;

use Streak\Domain\Event;
use Streak\Domain\Identifiable;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Listener extends Identifiable
{
    public function listenerId() : Listener\Id;

    /**
     * @return bool whether event was processed/is supported
     */
    public function on(Event $event) : bool;
}
