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

namespace Streak\Infrastructure\Application\Sensor\LoggingSensor;

use Psr\Log\LoggerInterface;
use Streak\Application\Sensor;
use Streak\Infrastructure\Application\Sensor\LoggingSensor;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Application\Sensor\LoggingSensor\FactoryTest
 */
class Factory implements Sensor\Factory
{
    public function __construct(private Sensor\Factory $factory, private LoggerInterface $logger)
    {
    }

    public function create(): Sensor
    {
        $sensor = $this->factory->create();

        return new LoggingSensor($sensor, $this->logger);
    }
}
