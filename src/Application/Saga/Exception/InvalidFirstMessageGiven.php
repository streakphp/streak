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

namespace Streak\Application\Saga\Exception;

use Streak\Domain\Message\Exception\InvalidMessageGiven;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class InvalidFirstMessageGiven extends InvalidMessageGiven
{
}
