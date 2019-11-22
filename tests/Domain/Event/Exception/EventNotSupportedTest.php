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

use PHPUnit\Framework\TestCase;
use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Id\UUID;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Exception\EventNotSupported
 */
class EventNotSupportedTest extends TestCase
{
    /**
     * @var Event\Envelope
     */
    private $event;

    protected function setUp()
    {
        $producerId = $this->getMockBuilder(Domain\Id::class)->getMockForAbstractClass();
        $event = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $this->event = new Event\Envelope(UUID::random(), 'event', $event, $producerId, null);
    }

    public function testException()
    {
        $exception = new EventNotSupported($this->event);

        $this->assertSame('Event "event" not supported.', $exception->getMessage());
        $this->assertSame($this->event, $exception->event());
    }
}
