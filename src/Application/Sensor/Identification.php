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

namespace Streak\Application\Sensor;

use Streak\Application\Sensor;
use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
trait Identification
{
    private $id;

    public function __construct(Sensor\Id $id)
    {
        $this->identifyBy($id);
    }

    public function sensorId() : Sensor\Id
    {
        return $this->id;
    }

    public function producerId() : Domain\Id
    {
        return $this->sensorId();
    }

    public function id() : Domain\Id
    {
        return $this->sensorId();
    }

    protected function identifyBy(Sensor\Id $id) : void
    {
        $this->id = $id;
    }
}
