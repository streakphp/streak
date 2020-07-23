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

namespace Streak\Application\Exception;

use Streak\Application;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class QueryNotSupported extends \RuntimeException
{
    /** @var Application\Query */
    private $query;

    public function __construct(Application\Query $query, \Exception $previous = null)
    {
        $this->query = $query;

        $message = sprintf('Query "%s" is not supported.', get_class($query));
        parent::__construct($message, 0, $previous);
    }

    public function query() : Application\Query
    {
        return $this->query;
    }
}
