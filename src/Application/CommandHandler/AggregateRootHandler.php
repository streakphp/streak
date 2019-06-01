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

use Streak\Application\Command;
use Streak\Application\CommandHandler;
use Streak\Application\Exception\CommandNotSupported;
use Streak\Domain\AggregateRoot;
use Streak\Domain\Exception\AggregateNotFound;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class AggregateRootHandler
{
    private $repository;

    public function __construct(AggregateRoot\Repository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(Command $command) : void
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

        $aggregate->handle($command);
    }
}
