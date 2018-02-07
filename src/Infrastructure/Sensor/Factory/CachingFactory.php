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
use Streak\Application\Sensor\Factory;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class CachingFactory implements Factory
{
    private $factory;
    private $cached;

    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    public function create() : Sensor
    {
        if (null !== $this->cached) {
            return $this->cached;
        }

        $this->cached = $this->factory->create();

        return $this->cached;
    }
}
