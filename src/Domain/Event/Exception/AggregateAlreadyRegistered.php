<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain\Event\Exception;

use Streak\Domain\EventSourced;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class AggregateAlreadyRegistered extends \InvalidArgumentException
{
    private $aggregate;

    public function __construct(EventSourced\Aggregate $aggregate, \Throwable $previous = null)
    {
        $this->aggregate = $aggregate;

        parent::__construct('Aggregate already registered.', 0, $previous);
    }

    public function aggregate() : EventSourced\Aggregate
    {
        return $this->aggregate;
    }
}
