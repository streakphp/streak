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
    private $given;
    private $expected;

    public function __construct(Domain\AggregateRootId $given, string $expected, \Throwable $previous = null)
    {
        $this->given = get_class($given);
        $this->expected = $expected;

        $message = sprintf('Invalid aggregate id given. Expected "%s" but got "%s".', $this->expected, $this->given);

        parent::__construct($message, 0, $previous);
    }

    public function given() : string
    {
        return $this->given;
    }

    public function expected() : string
    {
        return $this->expected;
    }

}
