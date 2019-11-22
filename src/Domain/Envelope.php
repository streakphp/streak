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

namespace Streak\Domain;

use Streak\Domain\Id\UUID;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Envelope extends ValueObject
{
    public function uuid() : UUID;

    public function name() : string;

    public function message();

    /**
     * @param string $name
     *
     * @return int|float|string|null
     */
    public function get($name);

    public function metadata() : array;
}
