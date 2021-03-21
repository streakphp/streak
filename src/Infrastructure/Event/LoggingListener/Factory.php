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

namespace Streak\Infrastructure\Event\LoggingListener;

use Psr\Log\LoggerInterface;
use Streak\Domain\Event;
use Streak\Infrastructure\Event\LoggingListener;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Event\LoggingListener\FactoryTest
 */
class Factory implements Event\Listener\Factory
{
    private Event\Listener\Factory $factory;
    private LoggerInterface $logger;

    public function __construct(Event\Listener\Factory $factory, LoggerInterface $logger)
    {
        $this->factory = $factory;
        $this->logger = $logger;
    }

    public function create(Event\Listener\Id $id) : Event\Listener
    {
        $saga = $this->factory->create($id);
        $saga = new LoggingListener($saga, $this->logger);

        return $saga;
    }

    public function createFor(Event\Envelope $event) : Event\Listener
    {
        $listener = $this->factory->createFor($event);
        $listener = new LoggingListener($listener, $this->logger);

        return $listener;
    }
}
