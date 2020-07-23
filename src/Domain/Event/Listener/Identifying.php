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
use Streak\Domain\Event\Listener;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
trait Identifying
{
    /** @var Listener\Id */
    private $id;

    public function __construct(Listener\Id $id)
    {
        $this->identifyBy($id);
    }

    public function listenerId() : Listener\Id
    {
        return $this->id;
    }

    public function id() : Domain\Id
    {
        return $this->listenerId();
    }

    protected function identifyBy(Listener\Id $id) : void
    {
        $this->id = $id;
    }
}
