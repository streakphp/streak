<?php

declare(strict_types=1);

/**
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain\Aggregate;

use Streak\Domain\Aggregate;
use Streak\Domain\Entity;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
trait Identification
{
    use Entity\Identification {
        Entity\Identification::identifyBy as identifyEntityBy;
    }

    public function __construct(Aggregate\Id $id)
    {
        $this->identifyBy($id);
    }

    public function identifyBy(Aggregate\Id $id) : void
    {
        $this->identifyEntityBy($id);
    }

    public function aggregateId() : Aggregate\Id
    {
        return $this->id;
    }
}
