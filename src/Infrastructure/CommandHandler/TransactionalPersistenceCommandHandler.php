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
use Streak\Domain\Event\Producer;
use Streak\Infrastructure;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class TransactionalPersistenceCommandHandler implements Application\CommandHandler
{
    /**
     * @var Application\CommandHandler
     */
    private $handler;

    /**
     * @var Infrastructure\UnitOfWork
     */
    private $uow;

    public function __construct(Application\CommandHandler $handler, Infrastructure\UnitOfWork $uow)
    {
        $this->handler = $handler;
        $this->uow = $uow;
    }

    public function handle(Application\Command $command) : void
    {
        $producersInTransactionBeforeCommand = $this->uow->uncommitted();
        try {
            $this->handler->handle($command);
            iterator_to_array($this->uow->commit());
        } catch (\Throwable $e) {
            $producersInTransactionAfterCommand = $this->uow->uncommitted();
            $producersAddedToTransactionWithinCommand = $this->findProducersAddedWhileHandlingCommand($producersInTransactionBeforeCommand, $producersInTransactionAfterCommand);

            foreach ($producersAddedToTransactionWithinCommand as $producer) {
                $this->uow->remove($producer);
            }

            throw $e;
        }
    }

    /**
     * Basically finds producers that are present in $producersInTransactionBeforeCommand, but not in $producersInTransactionAfterCommand.
     *
     * @param Producer[] $producersInTransactionBeforeCommand
     * @param Producer[] $producersInTransactionAfterCommand
     *
     * @return Producer[]
     */
    private function findProducersAddedWhileHandlingCommand(array $producersInTransactionBeforeCommand, array $producersInTransactionAfterCommand) : array
    {
        $producersAddedToTransactionWithinCommand = [];
        foreach ($producersInTransactionAfterCommand as $producerFromAfterCommandHandled) {
            foreach ($producersInTransactionBeforeCommand as $producerFromBeforeCommandHandled) {
                if ($producerFromAfterCommandHandled->producerId()->equals($producerFromBeforeCommandHandled->producerId())) {
                    continue 2; // next producer
                }
            }
            $producersAddedToTransactionWithinCommand[] = $producerFromAfterCommandHandled;
        }

        return $producersAddedToTransactionWithinCommand;
    }
}
