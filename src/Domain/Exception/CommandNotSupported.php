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
 *
 * @see \Streak\Domain\Exception\CommandNotSupportedTest
 */
class CommandNotSupported extends \RuntimeException
{
    public function __construct(private Domain\Command $command)
    {
        $message = \sprintf('Command "%s" is not supported.', $command::class);

        parent::__construct($message);
    }

    public function command(): Domain\Command
    {
        return $this->command;
    }
}
