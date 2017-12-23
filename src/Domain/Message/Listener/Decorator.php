<?php

declare(strict_types=1);

/**
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain\Message\Listener;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Decorator
{
    /**
     * @return object
     */
    public function decorated();
}
