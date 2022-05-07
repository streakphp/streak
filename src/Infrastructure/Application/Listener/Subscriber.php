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

namespace Streak\Infrastructure\Application\Listener;

use Doctrine\DBAL\Connection;
use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;

class Subscriber implements Event\Listener
{
    use Event\Listener\Filtering;
    use Event\Listener\Identifying;
    use Event\Listener\Listening;
    use Query\Handling;

    public function __construct(Subscriber\Id $id)
    {
        $this->identifyBy($id);
    }

    public function listenerId(): Subscriber\Id
    {
        return $this->id;
    }

    public function on(Envelope $event): bool
    {

    }
}
