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

namespace Streak\Domain\Event\Exception;

use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class InvalidEventGiven extends \InvalidArgumentException
{
    private $event;

    public function __construct(Event\Envelope $event, \Throwable $previous = null)
    {
        $this->event = $event;

        parent::__construct('Invalid event given.', 0, $previous);
    }

    public function event() : Event\Envelope
    {
        return $this->event;
    }
}
