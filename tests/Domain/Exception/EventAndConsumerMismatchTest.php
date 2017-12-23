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

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Exception\EventAndConsumerMismatch
 */
class EventAndConsumerMismatchTest extends TestCase
{
    /**
     * @var Event\Consumer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $consumer;

    /**
     * @var Domain\Event|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event;

    public function setUp()
    {
        $this->consumer = $this->getMockBuilder(Event\Consumer::class)->getMockForAbstractClass();
        $this->event = $this->getMockBuilder(Domain\Event::class)->getMockForAbstractClass();
    }

    public function testException()
    {
        $exception = new EventAndConsumerMismatch($this->consumer, $this->event);

        $this->assertSame($this->consumer, $exception->consumer());
        $this->assertSame($this->event, $exception->event());
    }
}
