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

namespace Streak\Domain\Event\Subscription\Exception;

use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\Id\UUID;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Subscription\Exception\EventIgnored
 */
class EventIgnoredTest extends TestCase
{
    private Event\Envelope $event;

    protected function setUp(): void
    {
        $this->event = Event\Envelope::new($this->getMockBuilder(Event::class)->setMockClassName('event1')->getMockForAbstractClass(), UUID::random());
    }

    public function testException(): void
    {
        $exception = new EventIgnored($this->event);

        self::assertSame('Event "event1" was ignored.', $exception->getMessage());
        self::assertSame($this->event, $exception->event());
    }
}
