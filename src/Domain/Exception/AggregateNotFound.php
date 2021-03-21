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

use Streak\Domain\Aggregate;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Domain\Exception\AggregateNotFoundTest
 */
class AggregateNotFound extends \RuntimeException
{
    private Aggregate\Id $aggregateId;

    public function __construct(Aggregate\Id $aggregateId, \Throwable $previous = null)
    {
        $this->aggregateId = $aggregateId;

        $message = sprintf('Aggregate "%s@%s" not found.', get_class($aggregateId), $this->aggregateId->toString());

        parent::__construct($message, 0, $previous);
    }

    public function aggregateId() : Aggregate\Id
    {
        return $this->aggregateId;
    }
}
