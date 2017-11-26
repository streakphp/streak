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
class InvalidAggregateGiven extends \InvalidArgumentException
{
    private $aggregate;

    public function __construct(Domain\AggregateRoot $aggregate, \Throwable $previous = null)
    {
        $this->aggregate = $aggregate;

        $message = sprintf('Invalid aggregate given.');

        parent::__construct($message, 0, $previous);
    }

    public function aggregate() : Domain\AggregateRoot
    {
        return $this->aggregate;
    }
}
