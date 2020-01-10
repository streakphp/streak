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

namespace Streak\Domain\Event\Listener;

use Streak\Domain\ValueObject;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface State extends ValueObject
{
    /**
     * @param string $name must be non empty string
     */
    public function has(string $name) : bool;

    /**
     * @param string $name must be non empty string
     *
     * @return string|array|null returns null, scalar of recursive array of null & scalar values
     *
     * @throws \OutOfBoundsException thrown if value not found
     */
    public function get(string $name);

    public function toArray() : array;

    /**
     * @param string            $name  must be non empty string
     * @param string|array|null $value must be null, scalar of recursive array of null & scalar values
     *
     * @return static
     */
    public function set(string $name, $value) : self;
}
