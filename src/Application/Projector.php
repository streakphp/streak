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

use Streak\Domain\Event;
use Streak\Domain\Resettable;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Projector extends Event\Listener, Resettable
{
}
