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

namespace Streak\Application;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface CommandHandler
{
    /**
     * @throws Exception\CommandNotSupported
     *
     * @TODO: rename to CommandHandler::handleCommand()
     */
    public function handle(Command $command): void;
}
