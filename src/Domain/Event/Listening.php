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

namespace Streak\Domain\Event;

use Streak\Domain;
use Streak\Domain\Message;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
trait Listening
{
    use Message\Listening {
        on as private;
        on as onMessage;
    }

    public function on(Domain\Message $event) : void
    {
        if (!$event instanceof Domain\Event) {
            throw new \InvalidArgumentException('Event expected but message given.');
        }

        $this->onMessage($event);
    }
}
