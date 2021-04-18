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

namespace Streak\Infrastructure\Application\CommandBus;

use Streak\Application;
use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Application\CommandBus\SynchronousCommandBusTest
 */
class SynchronousCommandBus implements Application\CommandBus
{
    /**
     * @var Domain\CommandHandler[]
     */
    private array $handlers = [];

    public function register(Domain\CommandHandler $handler): void
    {
        $this->handlers[] = $handler;
    }

    /**
     * @throws Domain\Exception\CommandNotSupported
     */
    public function dispatch(Domain\Command $command): void
    {
        foreach ($this->handlers as $handler) {
            try {
                $handler->handleCommand($command);

                return;
            } catch (Domain\Exception\CommandNotSupported $exception) {
                continue;
            }
        }

        throw new Domain\Exception\CommandNotSupported($command);
    }
}
