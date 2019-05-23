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

namespace Streak\Application\Listener\Subscriptions\Projector;

use Doctrine\DBAL\Connection;
use Streak\Application\Listener\Subscriptions\Projector;
use Streak\Domain\Clock;
use Streak\Domain\Event;
use Streak\Domain\Exception\InvalidIdGiven;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class Factory implements Event\Listener\Factory
{
    private $connection;
    private $clock;

    public function __construct(Connection $connection, Clock $clock)
    {
        $this->connection = $connection;
        $this->clock = $clock;
    }

    public function create(Event\Listener\Id $id) : Event\Listener
    {
        if (!$id instanceof Projector\Id) {
            throw new InvalidIdGiven($id);
        }

        return new Projector($id, $this->connection, $this->clock);
    }

    public function createFor(Event\Envelope $event) : Event\Listener
    {
        return $this->create(Projector::correlate($event));
    }
}
