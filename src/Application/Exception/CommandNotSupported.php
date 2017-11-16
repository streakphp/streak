<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Application\Exception;

use Streak\Application\Command;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class CommandNotSupported extends \RuntimeException
{
    private $command;

    public function __construct(Command $command, \Exception $previous = null)
    {
        $this->command = $command;

        $message = sprintf('Command "%s" is not supported.', get_class($command));
        parent::__construct($message, 0, $previous);
    }

    public function getCommand() : Command
    {
        return $this->command;
    }
}
