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

namespace Streak\Domain\Event;

use Streak\Domain;
use Streak\Domain\Exception;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Consumer
{
    /**
     * @throws Exception\EventAndConsumerMismatch
     */
    public function replay(Domain\Event ...$events) : void;

    public function lastReplayed() : ?Domain\Event;
}
