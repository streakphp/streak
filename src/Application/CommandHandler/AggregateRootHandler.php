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

namespace Streak\Application\CommandHandler;

use Streak\Domain\AggregateRoot;
use Streak\Domain\Command;
use Streak\Domain\CommandHandler;
use Streak\Domain\Exception\AggregateNotFound;
use Streak\Domain\Exception\CommandNotSupported;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Application\CommandHandler\AggregateRootHandlerTest
 */
class AggregateRootHandler implements CommandHandler
{
    private AggregateRoot\Repository $repository;

    public function __construct(AggregateRoot\Repository $repository)
    {
        $this->repository = $repository;
    }

    public function handleCommand(Command $command): void
    {
        if (!$command instanceof Command\AggregateRootCommand) {
            throw new CommandNotSupported($command);
        }

        $id = $command->aggregateRootId();
        $aggregate = $this->repository->find($id);

        if (null === $aggregate) {
            throw new AggregateNotFound($id);
        }

        if (!$aggregate instanceof CommandHandler) {
            throw new CommandNotSupported($command);
        }

        $aggregate->handleCommand($command);
    }
}
