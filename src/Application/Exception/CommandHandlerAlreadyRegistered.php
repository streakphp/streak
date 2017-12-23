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

use Streak\Application\CommandHandler;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class CommandHandlerAlreadyRegistered extends \OutOfRangeException
{
    private $handler;

    public function __construct(CommandHandler $handler, \Exception $previous = null)
    {
        $this->handler = $handler;

        $message = sprintf('Handler "%s" already registered.', get_class($handler));
        parent::__construct($message, 0, $previous);
    }

    public function handler() : CommandHandler
    {
        return $this->handler;
    }
}
