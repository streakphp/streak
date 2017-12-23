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

namespace Streak\Infrastructure\CommandHandler;

use Streak\Application;
use Streak\Application\Command;
use Streak\Application\Exception;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class CompositeCommandHandler implements Application\CommandHandler
{
    private $handlers = [];

    public function __construct(Application\CommandHandler ...$handlers)
    {
        foreach ($handlers as $handler) {
            try {
                $this->registerHandler($handler);
            } catch (Exception\CommandHandlerAlreadyRegistered $e) {
                continue;
            }
        }
    }

    /**
     * @throws Exception\CommandHandlerAlreadyRegistered
     */
    public function registerHandler(Application\CommandHandler $handler) : void
    {
        foreach ($this->handlers as $registered) {
            if ($handler === $registered) {
                throw new Exception\CommandHandlerAlreadyRegistered($handler);
            }
        }

        $this->handlers[] = $handler;
    }

    public function handle(Command $command) : void
    {
        $last = null;
        foreach ($this->handlers as $handler) {
            try {
                $handler->handle($command);

                return;
            } catch (Exception\CommandNotSupported $current) {
                $last = new Exception\CommandNotSupported($command, $current);
            }
        }

        throw new Exception\CommandNotSupported($command, $last);
    }
}
