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

use PHPUnit\Framework\TestCase;
use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Id\UUID;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Exception\EventAndConsumerMismatch
 */
class EventAndConsumerMismatchTest extends TestCase
{
    private Event\Consumer $consumer;

    private Domain\Event\Envelope $event;

    protected function setUp(): void
    {
        $this->consumer = $this->getMockBuilder(Event\Consumer::class)->getMockForAbstractClass();
        $this->event = Event\Envelope::new($this->getMockBuilder(Domain\Event::class)->getMockForAbstractClass(), UUID::random());
    }

    public function testException(): void
    {
        $exception = new EventAndConsumerMismatch($this->consumer, $this->event);

        self::assertSame($this->consumer, $exception->consumer());
        self::assertSame($this->event, $exception->event());
    }
}
