<?php

/**
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Streak\Domain\Exception;

use Streak\Domain\AggregateRoot;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Domain\Exception\InvalidAggregateIdGivenTest
 */
class InvalidAggregateIdGiven extends \InvalidArgumentException
{
    private AggregateRoot\Id $aggregateId;

    public function __construct(AggregateRoot\Id $aggregateId, \Throwable $previous = null)
    {
        $this->aggregateId = $aggregateId;

        $message = sprintf('Invalid aggregate id given.');

        parent::__construct($message, 0, $previous);
    }

    public function aggregateId() : AggregateRoot\Id
    {
        return $this->aggregateId;
    }
}
