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

namespace Streak\Application;

use Streak\Domain;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Saga extends Event\Listener, Event\Replayable, Event\Completable
{
//    public static function beginsWith(Domain\Event $event) : bool;

    public function sagaId() : Saga\Id;
}
