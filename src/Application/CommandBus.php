<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Application;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface CommandBus
{
    /**
     * @param Command $command
     *
     * @return void
     *
     * @throws Exception\CommandNotSupported
     */
    public function dispatch(Command $command) : void;
}
