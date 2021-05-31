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

namespace Streak\Infrastructure\Domain\Event\LoggingListener;

use Psr\Log\LoggerInterface;
use Streak\Domain\Event;
use Streak\Infrastructure\Domain\Event\LoggingListener;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Domain\Event\LoggingListener\FactoryTest
 */
class Factory implements \Streak\Application\Event\Listener\Factory
{
    private \Streak\Application\Event\Listener\Factory $factory;
    private LoggerInterface $logger;

    public function __construct(\Streak\Application\Event\Listener\Factory $factory, LoggerInterface $logger)
    {
        $this->factory = $factory;
        $this->logger = $logger;
    }

    public function create(\Streak\Application\Event\Listener\Id $id): \Streak\Application\Event\Listener
    {
        $saga = $this->factory->create($id);

        return new LoggingListener($saga, $this->logger);
    }

    public function createFor(Event\Envelope $event): \Streak\Application\Event\Listener
    {
        $listener = $this->factory->createFor($event);

        return new LoggingListener($listener, $this->logger);
    }
}
