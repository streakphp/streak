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

use Streak\Application\QueryHandler;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class QueryHandlerAlreadyRegistered extends \OutOfRangeException
{
    /** @var QueryHandler */
    private $handler;

    public function __construct(QueryHandler $handler, \Exception $previous = null)
    {
        $this->handler = $handler;

        $message = sprintf('Handler "%s" already registered.', get_class($handler));
        parent::__construct($message, 0, $previous);
    }

    public function handler() : QueryHandler
    {
        return $this->handler;
    }
}
