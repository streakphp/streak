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

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Application\CommandHandler\AggregateRootHandlerTest
 */
class AggregateRootHandler implements CommandHandler
{
    private AggregateRoot\Repository $repository;
    private AggregateRoot\Factory $factory;

    public function __construct(AggregateRoot\Factory $factory, AggregateRoot\Repository $repository)
    {
        $this->factory = $factory;
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
            $aggregate = $this->factory->create($id);
        }

        if (!$aggregate instanceof CommandHandler) {
            throw new CommandNotSupported($command);
        }

        $aggregate->handle($command);
    }
}
