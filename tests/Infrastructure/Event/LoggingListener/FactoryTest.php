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
use Streak\Application\Saga;
use Streak\Domain\Event;
use Streak\Infrastructure\Event\LoggingListener;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Event\LoggingListener\Factory
 */
class FactoryTest extends TestCase
{
    /**
     * @var Saga\Factory|MockObject
     */
    private $factory;

    /**
     * @var Saga|MockObject
     */
    private $saga;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var Saga\Id|MockObject
     */
    private $sagaId;

    /**
     * @var Event|MockObject
     */
    private $event;

    protected function setUp()
    {
        $this->factory = $this->getMockBuilder(Saga\Factory::class)->setMockClassName('SagaFactoryMock001')->getMockForAbstractClass();
        $this->saga = $this->getMockBuilder(Saga::class)->setMockClassName('SagaMock001')->getMockForAbstractClass();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        $this->sagaId = $this->getMockBuilder(Saga\Id::class)->getMockForAbstractClass();
        $this->event = $this->getMockBuilder(Event::class)->setMockClassName('EventMock001')->getMockForAbstractClass();
    }

    public function testFactory()
    {
        $factory = new Factory($this->factory, $this->logger);
        $saga = new LoggingListener($this->saga, $this->logger);

        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($this->sagaId)
            ->willReturn($this->saga)
        ;

        $this->factory
            ->expects($this->once())
            ->method('createFor')
            ->with($this->event)
            ->willReturn($this->saga)
        ;

        $this->assertEquals($saga, $factory->create($this->sagaId));
        $this->assertEquals($saga, $factory->createFor($this->event));
    }
}
