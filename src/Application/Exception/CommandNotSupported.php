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
 *
 * @see \Streak\Application\Exception\CommandNotSupportedTest
 */
class CommandNotSupported extends \RuntimeException
{
    private Application\Command $command;

    public function __construct(Application\Command $command, \Exception $previous = null)
    {
        $this->command = $command;

        $message = sprintf('Command "%s" is not supported.', \get_class($command));
        parent::__construct($message, 0, $previous);
    }

    public function command(): Application\Command
    {
        return $this->command;
    }
}
