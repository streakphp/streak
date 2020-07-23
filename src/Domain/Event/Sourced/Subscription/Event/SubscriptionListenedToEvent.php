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

namespace Streak\Domain\Event\Sourced\Subscription\Event;

use Streak\Domain\Event;
use Streak\Domain\Event\Sourced\Subscription;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class SubscriptionListenedToEvent implements Subscription\Event
{
    const DATE_FORMAT = 'U.u';

    /** @var Event\Envelope */
    private $event;
    /** @var string */
    private $timestamp;

    public function __construct(Event\Envelope $event, \DateTimeInterface $timestamp)
    {
        $this->event = $event;
        $this->timestamp = $timestamp->format(self::DATE_FORMAT);
    }

    public function event() : Event\Envelope
    {
        return $this->event;
    }

    /**
     * @psalm-suppress InvalidFalsableReturnType
     */
    public function timestamp() : \DateTimeImmutable
    {
        /**
         * @psalm-suppress FalsableReturnStatement
         */
        return \DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $this->timestamp);
    }
}
