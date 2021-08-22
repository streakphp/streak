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

namespace Streak\Infrastructure\Application\Sensor;

use Streak\Application\Sensor;
use Streak\Domain\Event;
use Streak\Infrastructure\Domain\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Application\Sensor\CommittingSensorTest
 */
class CommittingSensor implements Sensor
{
    public function __construct(private Sensor $sensor, private UnitOfWork $uow)
    {
    }

    public function id(): Sensor\Id
    {
        return $this->sensor->id();
    }

    /**
     * @return Event\Envelope[]
     */
    public function events(): array
    {
        return $this->sensor->events();
    }

    public function process(...$messages): void
    {
        $this->sensor->process(...$messages);

        iterator_to_array($this->uow->commit());
    }
}
