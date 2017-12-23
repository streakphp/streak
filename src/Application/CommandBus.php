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

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface CommandBus
{
    /**
     * @throws Exception\CommandHandlerAlreadyRegistered
     */
    public function register(CommandHandler $handler) : void;

    /**
     * @throws Exception\CommandNotSupported
     */
    public function dispatch(Command $command) : void;
}
