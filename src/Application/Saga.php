<?php

/*
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
use Streak\Domain\Message;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Saga extends Message\Replayable
{
    // TODO: maybe begin()?
    public function beginsWith(Domain\Message $message) : bool;

    public function on(Domain\Message $message, CommandBus $bus) : void;

    // TODO: maybe finished()?
    public function isFinished() : bool;
}
