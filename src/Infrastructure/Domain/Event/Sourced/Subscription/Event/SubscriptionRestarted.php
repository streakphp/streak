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

namespace Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event;

use Streak\Domain\Event;
use Streak\Domain\Event\Sourced\Subscription;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Domain\Event\Sourced\Subscription\Event\SubscriptionRestartedTest
 */
class SubscriptionRestarted implements Subscription\Event
{
    private const DATE_FORMAT = 'U.u';

    private Event\Envelope $event;
    private string $timestamp;

    public function __construct(Event\Envelope $originallyStartedBy, \DateTimeInterface $timestamp)
    {
        $this->event = $originallyStartedBy;
        $this->timestamp = $timestamp->format(self::DATE_FORMAT);
    }

    public function originallyStartedBy(): Event\Envelope
    {
        return $this->event;
    }

    public function timestamp(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $this->timestamp);
    }
}