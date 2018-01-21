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

namespace Streak\Domain\Event\Sourced\Entity;

use Streak\Domain;
use Streak\Domain\Entity;
use Streak\Domain\Event\Producer;
use Streak\Domain\Event\Sourced as EventSourced;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
trait Identification // implements Producer
{
    use Entity\Identification {
        Entity\Identification::identifyBy as private identifyEntityBy;
    }

    public function __construct(EventSourced\Entity\Id $id)
    {
        $this->identifyBy($id);
    }

    public function producerId() : Domain\Id
    {
        return $this->id;
    }

    protected function identifyBy(Entity\Id $id) : void
    {
        $this->identifyEntityBy($id);
    }
}
