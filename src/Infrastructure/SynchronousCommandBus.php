<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Infrastructure;

use Application;
use Application\Command;
use Application\CommandHandler;
use Application\Exception;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class SynchronousCommandBus implements Application\CommandBus
{
    /**
     * @var CommandHandler[]
     */
    private $handlers = [];

    /**
     * @param CommandHandler $handler
     *
     * @throws Exception\CommandHandlerAlreadyRegistered
     */
    public function registerHandler(CommandHandler $handler) : void
    {
        foreach ($this->handlers as $registered) {
            if ($handler === $registered) {
                throw new Exception\CommandHandlerAlreadyRegistered($handler);
            }
        }

        $this->handlers[] = $handler;
    }

    /**
     * @param Command $command
     *
     * @return void
     *
     * @throws Exception\CommandNotSupported
     */
    public function dispatch(Command $command) : void
    {
        foreach ($this->handlers as $handler) {
            if (true === $handler->supports($command)) {
                $handler->handle($command);
                return ;
            }
        }

        throw new Exception\CommandNotSupported($command);
    }
}
