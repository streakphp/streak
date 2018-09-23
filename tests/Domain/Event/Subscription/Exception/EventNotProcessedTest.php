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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionListenedToEvent;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Subscription\Exception\EventNotProcessed
 */
class EventNotProcessedTest extends TestCase
{
    /**
     * @var SubscriptionListenedToEvent|MockObject
     */
    private $event;

    protected function setUp()
    {
        $this->event = $this->getMockBuilder(Event::class)->setMockClassName('event1')->getMockForAbstractClass();
        $this->event = new SubscriptionListenedToEvent($this->event, 1, new \DateTime());
    }

    public function testException()
    {
        $exception = new EventNotProcessed($this->event);

        $this->assertSame('Event "event1" was not processed.', $exception->getMessage());
        $this->assertSame($this->event, $exception->event());
    }
}
