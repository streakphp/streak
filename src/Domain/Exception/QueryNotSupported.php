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

use Streak\Domain\Query;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Domain\Exception\QueryNotSupportedTest
 */
class QueryNotSupported extends \RuntimeException
{
    public function __construct(private Query $query)
    {
        $message = \sprintf('Query "%s" is not supported.', $query::class);
        parent::__construct($message);
    }

    public function query(): Query
    {
        return $this->query;
    }
}
