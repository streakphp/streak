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

namespace Streak\Infrastructure\Domain\Event\LoggingListener;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Streak\Domain\Event;
use Streak\Application\Event\Listener;
use Streak\Infrastructure\Domain\Event\LoggingListener;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\Event\LoggingListener\Factory
 */
class FactoryTest extends TestCase
{
    private Listener\Factory $factory;

    private Listener $listener;

    private LoggerInterface $logger;

    private Listener\Id $id;

    private Event\Envelope $event;

    protected function setUp(): void
    {
        $this->factory = $this->getMockBuilder(Listener\Factory::class)->setMockClassName('ListenerFactoryMock001')->getMockForAbstractClass();
        $this->listener = $this->getMockBuilder(Listener::class)->setMockClassName('ListenerMock001')->addMethods(['replay', 'reset', 'completed'])->getMockForAbstractClass();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        $this->id = $this->getMockBuilder(Listener\Id::class)->getMockForAbstractClass();
        $this->event = Event\Envelope::new($this->getMockBuilder(Event::class)->setMockClassName('EventMock001')->getMockForAbstractClass(), $this->id, 1);
    }

    public function testFactory(): void
    {
        $factory = new Factory($this->factory, $this->logger);
        $listener = new LoggingListener($this->listener, $this->logger);

        $this->factory
            ->expects(self::once())
            ->method('create')
            ->with($this->id)
            ->willReturn($this->listener)
        ;
        $this->factory
            ->expects(self::once())
            ->method('createFor')
            ->with($this->event)
            ->willReturn($this->listener)
        ;

        self::assertEquals($listener, $factory->create($this->id));
        self::assertEquals($listener, $factory->createFor($this->event));
    }
}
