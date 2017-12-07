<?php

/*
 * This file is part of the cbs package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Streak\Domain;

use Streak\Domain\Persistable\State;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Persistable
{
    public function from(State $state) : void;

    public function to(State $state) : void;
}
