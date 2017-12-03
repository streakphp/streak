<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Application\Memento;

use Streak\Application\Memento;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Originator
{
    public function from(Memento $memento) : void;

    public function to(Memento $memento) : void;
}
