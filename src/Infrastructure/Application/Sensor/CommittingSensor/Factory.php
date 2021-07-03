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

namespace Streak\Infrastructure\Application\Sensor\CommittingSensor;

use Streak\Application\Sensor;
use Streak\Infrastructure\Application\Sensor\CommittingSensor;
use Streak\Infrastructure\Domain\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Application\Sensor\CommittingSensor\FactoryTest
 */
class Factory implements Sensor\Factory
{
    public function __construct(private Sensor\Factory $factory, private UnitOfWork $uow)
    {
    }

    public function create(): Sensor
    {
        $sensor = $this->factory->create();

        $this->uow->add($sensor);

        return new CommittingSensor($sensor, $this->uow);
    }
}
