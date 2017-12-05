<?php

/*
 * This file is part of the cbs package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain\Persistable;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface State
{
    /**
     * @param string $name
     * @param mixed $value
     */
    public function set(string $name, $value) : void;

    /**
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $name, $default = null);
}
