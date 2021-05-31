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

namespace Streak\Infrastructure\Domain\Event;

use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\Id\UUID;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\Event\NullListener
 */
class NullListenerTest extends TestCase
{
    private \Streak\Application\Event\Listener $listener;

    private \Streak\Application\Event\Listener\Id $id;

    private Event\Envelope $event;

    protected function setUp(): void
    {
        $this->listener = $this->getMockBuilder(\Streak\Application\Event\Listener::class)->addMethods(['replay', 'reset', 'completed'])->getMockForAbstractClass();
        $this->id = $this->getMockBuilder(\Streak\Application\Event\Listener\Id::class)->getMockForAbstractClass();
        $this->event = Event\Envelope::new($this->getMockBuilder(Event::class)->getMockForAbstractClass(), UUID::random());
    }

    public function testObject(): void
    {
        $this->listener
            ->expects(self::atLeastOnce())
            ->method('listenerId')
            ->willReturn($this->id)
        ;

        $listener = NullListener::from($this->listener);

        self::assertInstanceOf(NullListener::class, $listener);
        self::assertSame($this->id, $listener->listenerId());

        self::assertTrue($listener->on($this->event));
    }
}
