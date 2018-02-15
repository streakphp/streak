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
        $this->handler->handle($command);

        $this->uow->commit();
    }
}
