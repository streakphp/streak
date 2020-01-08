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

use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\Id\UUID;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionStarted
 */
class SubscriptionStartedTest extends TestCase
{
    private $event;

    protected function setUp()
    {
        $this->event = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $this->event = Event\Envelope::new($this->event, UUID::random());
    }

    public function testObject()
    {
        $event = new SubscriptionStarted($this->event, $now = new \DateTime());

        $this->assertSame($this->event, $event->startedBy());
        $this->assertEquals($now, $event->timestamp());
    }
}
