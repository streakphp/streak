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

namespace Streak\Infrastructure\Saga\LoggingSaga;

use Psr\Log\LoggerInterface;
use Streak\Application\Saga;
use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Infrastructure\Saga\LoggingSaga;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class Factory implements Saga\Factory
{
    private $factory;
    private $logger;

    public function __construct(Saga\Factory $factory, LoggerInterface $logger)
    {
        $this->factory = $factory;
        $this->logger = $logger;
    }

    public function create(Domain\Id $id) : Listener
    {
        $saga = $this->factory->create($id);
        $saga = new LoggingSaga($saga, $this->logger);

        return $saga;
    }

    public function createFor(Event $event) : Listener
    {
        $saga = $this->factory->createFor($event);
        $saga = new LoggingSaga($saga, $this->logger);

        return $saga;
    }
}
