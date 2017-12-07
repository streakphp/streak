<?php

/*
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
class CommandTransactionCompromised extends \LogicException
{
    private $command;

    public function __construct(Application\Command $command, \Throwable $previous = null)
    {
        $this->command = $command;

        $message = sprintf('Command "%s" made changes on more than one aggregate.', get_class($command));

        parent::__construct($message, 0, $previous);

    }

    public function command() : Application\Command
    {
        return $this->command;
    }
}
