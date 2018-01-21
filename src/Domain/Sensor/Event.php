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

namespace Streak\Domain\Sensor;

use Streak\Domain;
use Streak\Domain\Sensor;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Event extends Domain\Event
{
//    public function producerId() : Actor\Id;

    public function sensorId() : Sensor\Id;
}
