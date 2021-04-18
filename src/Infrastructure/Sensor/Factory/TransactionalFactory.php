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

namespace Streak\Infrastructure\Sensor\Factory;

use Streak\Application\Sensor;
use Streak\Infrastructure\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Sensor\Factory\TransactionalFactoryTest
 */
class TransactionalFactory implements Sensor\Factory
{
    private Sensor\Factory $factory;
    private UnitOfWork $uow;

    public function __construct(Sensor\Factory $factory, UnitOfWork $uow)
    {
        $this->factory = $factory;
        $this->uow = $uow;
    }

    public function create(): Sensor
    {
        $sensor = $this->factory->create();

        $this->uow->add($sensor);

        return $sensor;
    }
}
