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

use Streak\Application\CommandBus;
use Streak\Domain;
use Streak\Infrastructure\Domain\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Application\CommandBus\CommittingCommandBusTest
 */
class CommittingCommandBus implements CommandBus
{
    private int $transactions = 0;

    public function __construct(private CommandBus $bus, private UnitOfWork $uow)
    {
    }

    public function dispatch(Domain\Command $command): void
    {
        try {
            $this->begin();
            $this->bus->dispatch($command);
            $this->commit();
        } catch (\Throwable $exception) {
            $this->rollback();

            throw $exception;
        }
    }

    public function transactions(): int
    {
        return $this->transactions;
    }

    private function begin(): void
    {
        $this->transactions++;
    }

    private function commit(): void
    {
        $this->transactions--;

        if ($this->transactions > 0) { // not a last transaction
            return;
        }

        iterator_to_array($this->uow->commit());
    }

    private function rollback(): void
    {
        $this->transactions = 0;
    }
}
