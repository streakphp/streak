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

    public function id(): Sensor\Id
    {
        return $this->id;
    }

    protected function identifyBy(Sensor\Id $id): void
    {
        $this->id = $id;
    }
}
