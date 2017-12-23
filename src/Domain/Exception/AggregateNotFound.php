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

use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class AggregateNotFound extends \RuntimeException
{
    private $aggregateId;

    public function __construct(Domain\Aggregate\Id $aggregateId, \Throwable $previous = null)
    {
        $this->aggregateId = $aggregateId;

        $message = sprintf('Aggregate "%s" not found.', $this->aggregateId->toString());

        parent::__construct($message, 0, $previous);
    }

    public function aggregateId() : Domain\Aggregate\Id
    {
        return $this->aggregateId;
    }
}
