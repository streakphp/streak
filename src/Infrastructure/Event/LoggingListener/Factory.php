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
use Streak\Domain\Event\Listener;
use Streak\Infrastructure\Event\LoggingListener;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class Factory implements Listener\Factory
{
    private $factory;
    private $logger;

    public function __construct(Listener\Factory $factory, LoggerInterface $logger)
    {
        $this->factory = $factory;
        $this->logger = $logger;
    }

    public function create(Listener\Id $id) : Listener
    {
        $saga = $this->factory->create($id);
        $saga = new LoggingListener($saga, $this->logger);

        return $saga;
    }

    public function createFor(Event $event) : Listener
    {
        $listener = $this->factory->createFor($event);
        $listener = new LoggingListener($listener, $this->logger);

        return $listener;
    }
}
