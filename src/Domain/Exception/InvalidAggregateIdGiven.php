<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain\Exception;

use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class InvalidAggregateIdGiven extends \InvalidArgumentException
{
    private $aggregateId;

    public function __construct(Domain\AggregateRootId $aggregateId, \Throwable $previous = null)
    {
        $this->aggregateId = $aggregateId;

        $message = sprintf('Invalid aggregate id given.');

        parent::__construct($message, 0, $previous);
    }

    public function aggregateId() : Domain\AggregateRootId
    {
        return $this->aggregateId;
    }
}
