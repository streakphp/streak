<?php
/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain\Event;

use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Sourced extends Domain\Entity
{
    public function replay(Domain\Event ...$events) : void;

    public function lastReplayed() : Domain\Event;

    /**
     * @return Domain\Event[]
     */
    public function events() : array;
}
