<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface EventStore
{
    public function addEvents(AggregateRoot $aggregate, Event ...$events);

    /**
     * @return Event[]
     */
    public function getEvents(AggregateRoot $aggregate) : array;
}
