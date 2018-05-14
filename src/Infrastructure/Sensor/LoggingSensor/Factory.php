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

namespace Streak\Infrastructure\Sensor\LoggingSensor;

use Psr\Log\LoggerInterface;
use Streak\Application\Sensor;
use Streak\Infrastructure\Sensor\LoggingSensor;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class Factory implements Sensor\Factory
{
    private $factory;
    private $logger;

    public function __construct(Sensor\Factory $factory, LoggerInterface $logger)
    {
        $this->factory = $factory;
        $this->logger = $logger;
    }

    public function create() : Sensor
    {
        $sensor =  $this->factory->create();
        $sensor = new LoggingSensor($sensor, $this->logger);

        return $sensor;
    }
}
