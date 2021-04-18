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
use Streak\Domain;
use Streak\Domain\Event;
use Streak\Infrastructure\Domain\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Application\Sensor\CommittingSensorTest
 */
class CommittingSensor implements Sensor
{
    private Sensor $sensor;
    private UnitOfWork $uow;

    public function __construct(Sensor $sensor, UnitOfWork $uow)
    {
        $this->sensor = $sensor;
        $this->uow = $uow;
    }

    public function producerId(): Domain\Id
    {
        return $this->sensor->producerId();
    }

    /**
     * @return Event\Envelope[]
     */
    public function events(): array
    {
        return $this->sensor->events();
    }

    public function sensorId(): Sensor\Id
    {
        return $this->sensor->sensorId();
    }

    public function process(...$messages): void
    {
        $this->sensor->process(...$messages);

        iterator_to_array($this->uow->commit());
    }
}
