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

namespace Streak\Infrastructure\CommandBus;

use Streak\Application;
use Streak\Application\Command;
use Streak\Application\CommandHandler;
use Streak\Application\Exception;
use Streak\Infrastructure\CommandHandler\CompositeCommandHandler;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\CommandBus\SynchronousCommandBusTest
 */
class SynchronousCommandBus implements Application\CommandBus
{
    private CompositeCommandHandler $handler;

    public function __construct()
    {
        $this->handler = new CompositeCommandHandler();
    }

    /**
     * @throws Exception\CommandHandlerAlreadyRegistered
     */
    public function register(CommandHandler $handler): void
    {
        $this->handler->registerHandler($handler);
    }

    /**
     * @throws Exception\CommandNotSupported
     */
    public function dispatch(Command $command): void
    {
        try {
            $this->handler->handle($command);
        } catch (Exception\CommandNotSupported $previous) {
            throw new Exception\CommandNotSupported($command, $previous);
        }
    }
}
