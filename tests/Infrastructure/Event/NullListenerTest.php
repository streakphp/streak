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

namespace Streak\Infrastructure\Event;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\Id\UUID;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Event\NullListener
 */
class NullListenerTest extends TestCase
{
    /**
     * @var Event\Listener|MockObject
     */
    private $listener;

    /**
     * @var Event\Listener\Id|MockObject
     */
    private $id;

    /**
     * @var Event|MockObject
     */
    private $event;

    protected function setUp() : void
    {
        $this->listener = $this->getMockBuilder(Event\Listener::class)->setMethods(['replay', 'reset', 'completed'])->getMockForAbstractClass();
        $this->id = $this->getMockBuilder(Event\Listener\Id::class)->getMockForAbstractClass();
        $this->event = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $this->event = Event\Envelope::new($this->event, UUID::random());
    }

    public function testObject()
    {
        $this->listener
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id)
        ;

        $listener = NullListener::from($this->listener);

        $this->assertInstanceOf(NullListener::class, $listener);
        $this->assertSame($this->id, $listener->listenerId());

        $this->assertTrue($listener->on($this->event));
    }
}
