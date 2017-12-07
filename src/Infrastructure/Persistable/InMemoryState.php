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

namespace Streak\Infrastructure\Persistable;

use Streak\Domain\Persistable;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class InMemoryState implements Persistable\State
{
    private $values = [];

    public function set(string $name, $value) : void
    {
        $this->values[$name] = $value;
    }

    public function get(string $name, $default = null)
    {
        if (false === array_key_exists($name, $this->values)) {
            return $default;
        }

        return $this->values[$name];
    }
}
