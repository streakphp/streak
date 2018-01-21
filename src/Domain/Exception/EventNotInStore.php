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

namespace Streak\Domain\Exception;

use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class EventNotInStore extends \InvalidArgumentException
{
    private $event;

    public function __construct(Domain\Event $event, \Throwable $previous = null)
    {
        $this->event = $event;

        $message = sprintf('Event not in store.');

        parent::__construct($message, 0, $previous);
    }

    public function event() : Domain\Event
    {
        return $this->event;
    }
}
