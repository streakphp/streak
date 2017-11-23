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
use Streak\Domain\Exception;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface AggregateRootFactory
{
    /**
     * @throws Exception\InvalidAggregateIdGiven
     */
    public function create(Domain\AggregateRootId $id) : AggregateRoot;
}
