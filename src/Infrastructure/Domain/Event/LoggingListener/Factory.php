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
class Factory implements Event\Listener\Factory
{
    public function __construct(private Event\Listener\Factory $factory, private LoggerInterface $logger)
    {
    }

    public function create(Event\Listener\Id $id): Event\Listener
    {
        $saga = $this->factory->create($id);

        return new LoggingListener($saga, $this->logger);
    }

    public function createFor(Event\Envelope $event): Event\Listener
    {
        $listener = $this->factory->createFor($event);

        return new LoggingListener($listener, $this->logger);
    }
}
