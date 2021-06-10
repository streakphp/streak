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

namespace Streak\Infrastructure\Application\Sensor\Factory;

use Streak\Application\Sensor;
use Streak\Application\Sensor\Factory;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Application\Sensor\Factory\CachingFactoryTest
 */
class CachingFactory implements Factory
{
    private ?Sensor $cached = null;

    public function __construct(private Factory $factory)
    {
    }

    public function create(): Sensor
    {
        if (null !== $this->cached) {
            return $this->cached;
        }

        $this->cached = $this->factory->create();

        return $this->cached;
    }
}
