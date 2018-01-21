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

namespace Streak\Domain\AggregateRoot;

use Streak\Domain\Aggregate;
use Streak\Domain\AggregateRoot;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
trait Identification
{
    use Aggregate\Identification {
        Aggregate\Identification::identifyBy as private identifyAggregateBy;
    }

    public function __construct(AggregateRoot\Id $id)
    {
        $this->identifyBy($id);
    }

    public function aggregateRootId() : AggregateRoot\Id
    {
        return $this->id;
    }

    protected function identifyBy(AggregateRoot\Id $id) : void
    {
        $this->identifyAggregateBy($id);
    }
}
