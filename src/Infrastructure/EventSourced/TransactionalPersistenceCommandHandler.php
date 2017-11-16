<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Infrastructure\EventSourced;

use Streak\Application;
use Streak\Infrastructure\EventSourced;

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
     * @var EventSourced\UnitOfWork
     */
    private $uow;

    public function __construct(Application\CommandHandler $handler, EventSourced\UnitOfWork $uow)
    {
        $this->handler = $handler;
        $this->uow = $uow;
    }

    public function handle(Application\Command $command) : void
    {
        $this->uow->clear();

        $this->handler->handle($command);

        if ($this->uow->count() > 1) {
            throw new Exception\CommandTransactionCompromised($command);
        }

        $this->uow->commit();
    }
}
