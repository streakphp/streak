<?php

/*
 * This file is part of the cbs package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain\Exception;

use Streak\Domain\AggregateRoot;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class AggregateNotFound extends \RuntimeException
{
    private $aggregateId;

    public function __construct(AggregateRoot\Id $aggregateId, \Throwable $previous = null)
    {
        $this->aggregateId = $aggregateId;

        $message = sprintf('Aggregate "%s" not found.', $this->aggregateId->toString());

        parent::__construct($message, 0, $previous);
    }

    /**
     * @return AggregateRoot\Id
     */
    public function getAggregateId() : AggregateRoot\Id
    {
        return $this->aggregateId;
    }
}
