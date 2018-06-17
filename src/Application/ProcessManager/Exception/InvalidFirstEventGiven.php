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

namespace Streak\Application\ProcessManager\Exception;

use Streak\Domain\Event\Exception\InvalidEventGiven;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class InvalidFirstEventGiven extends InvalidEventGiven
{
}
