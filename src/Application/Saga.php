<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Application;

use Streak\Domain;
use Streak\Domain\Message;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Saga extends Message\Listener
{
    public static function startsFor(Domain\Message $message) : bool;

    public static function stopsFor(Domain\Message $message) : bool;
}
