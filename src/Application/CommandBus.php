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

use Streak\Application\Exception;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface CommandBus
{
    /**
     * @throws Exception\CommandHandlerAlreadyRegistered
     */
    public function registerHandler(CommandHandler $handler) : void;

    /**
     * @throws Exception\CommandNotSupported
     */
    public function dispatch(Command $command) : void;
}
