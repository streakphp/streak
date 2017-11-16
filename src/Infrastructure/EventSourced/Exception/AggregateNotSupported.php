<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Infrastructure\EventSourced\Exception;

use Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class AggregateNotSupported extends \RuntimeException
{
    private $aggregate;

    public function __construct(Domain\AggregateRoot $aggregate, \Throwable $previous = null)
    {
        $this->aggregate = $aggregate;

        $message = sprintf('Aggregate "%s" is not supported.', get_class($aggregate));

        parent::__construct($message, 0, $previous);
    }
}
