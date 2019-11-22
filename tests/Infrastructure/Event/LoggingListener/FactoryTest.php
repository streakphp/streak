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

namespace Streak\Infrastructure\Event\LoggingListener;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Infrastructure\Event\LoggingListener;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Event\LoggingListener\Factory
 */
class FactoryTest extends TestCase
{
    /**
     * @var Listener\Factory|MockObject
     */
    private $factory;

    /**
     * @var Listener|MockObject
     */
    private $listener;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var Listener\Id|MockObject
     */
    private $id;

    /**
     * @var Event|MockObject
     */
    private $event;

    protected function setUp()
    {
        $this->factory = $this->getMockBuilder(Listener\Factory::class)->setMockClassName('ListenerFactoryMock001')->getMockForAbstractClass();
        $this->listener = $this->getMockBuilder(Listener::class)->setMockClassName('ListenerMock001')->setMethods(['replay', 'reset', 'completed'])->getMockForAbstractClass();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        $this->id = $this->getMockBuilder(Listener\Id::class)->getMockForAbstractClass();
        $this->event = $this->getMockBuilder(Event::class)->setMockClassName('EventMock001')->getMockForAbstractClass();
        $this->event = Event\Envelope::new($this->event, $this->id, 1);
    }

    public function testFactory()
    {
        $factory = new Factory($this->factory, $this->logger);
        $listener = new LoggingListener($this->listener, $this->logger);

        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($this->id)
            ->willReturn($this->listener)
        ;
        $this->factory
            ->expects($this->once())
            ->method('createFor')
            ->with($this->event)
            ->willReturn($this->listener)
        ;

        $this->assertEquals($listener, $factory->create($this->id));
        $this->assertEquals($listener, $factory->createFor($this->event));
    }
}
