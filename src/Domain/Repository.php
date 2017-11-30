<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain;

use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Repository
{
    public function add(Domain\AggregateRoot $aggregate) : void;

    /**
     * @throws Exception\AggregateNotSupported
     */
    public function find(Domain\AggregateRoot\Id $id) : ?AggregateRoot;
}
