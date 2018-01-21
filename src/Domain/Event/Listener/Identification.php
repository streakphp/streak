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

namespace Streak\Domain\Event\Listener;

use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
trait Identification
{
    private $id;

    public function __construct(Domain\Id $id)
    {
        $this->identifyBy($id);
    }

    public function id() : Domain\Id
    {
        return $this->id;
    }

    protected function identifyBy(Domain\Id $id) : void
    {
        $this->id = $id;
    }
}
