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

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Exception\TooManyEventApplyingMethodsFound
 */
class TooManyEventApplyingMethodsFoundTest extends TestCase
{
    /**
     * @var Event\Consumer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $consumer;

    /**
     * @var Domain\Event\Envelope|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event;

    public function setUp()
    {
        $this->consumer = $this->getMockBuilder(Event\Consumer::class)->getMockForAbstractClass();
        $event = $this->getMockBuilder(Domain\Event::class)->getMockForAbstractClass();
        $this->event = Event\Envelope::new($event, Domain\Id\UUID::random());
    }

    public function testException()
    {
        $exception = new TooManyEventApplyingMethodsFound($this->consumer, $this->event);

        $this->assertSame($this->consumer, $exception->consumer());
        $this->assertSame($this->event, $exception->event());
    }
}
