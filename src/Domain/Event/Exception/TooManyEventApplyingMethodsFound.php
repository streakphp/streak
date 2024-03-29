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
 *
 * @see \Streak\Domain\Event\Exception\TooManyEventApplyingMethodsFoundTest
 */
class TooManyEventApplyingMethodsFound extends \BadMethodCallException
{
    public function __construct(private object $object, private Event\Envelope $event, \Throwable $previous = null)
    {
        parent::__construct('Too many event applying methods found.', 0, $previous);
    }

    public function object(): object
    {
        return $this->object;
    }

    public function event(): Event\Envelope
    {
        return $this->event;
    }
}
