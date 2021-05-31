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

use Doctrine\DBAL\Driver\Connection;
use Streak\Application\CommandBus;
use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Application\CommandBus\DbalTransactionalCommandBusTest
 */
class DbalTransactionalCommandBus implements CommandBus
{
    private CommandBus $bus;
    private Connection $connection;

    public function __construct(CommandBus $bus, Connection $connection)
    {
        $this->bus = $bus;
        $this->connection = $connection;
    }

    public function dispatch(Domain\Command $command): void
    {
        $this->connection->beginTransaction();

        try {
            $this->bus->dispatch($command);
            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();

            throw $exception;
        }
    }
}
