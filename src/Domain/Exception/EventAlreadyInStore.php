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

use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class EventAlreadyInStore extends \InvalidArgumentException
{
    private $event;

    public function __construct(Event\Envelope $event, \Throwable $previous = null)
    {
        $this->event = $event;

        $message = sprintf('Event already stored.');

        parent::__construct($message, 0, $previous);
    }

    public function event() : Event\Envelope
    {
        return $this->event;
    }
}
