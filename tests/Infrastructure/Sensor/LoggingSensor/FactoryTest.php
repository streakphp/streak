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

namespace Streak\Infrastructure\Sensor\LoggingSensor;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Streak\Application\Sensor;
use Streak\Domain\Event;
use Streak\Infrastructure\Sensor\LoggingSensor;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Sensor\LoggingSensor\Factory
 */
class FactoryTest extends TestCase
{
    /**
     * @var Sensor\Factory|MockObject
     */
    private $factory;

    /**
     * @var Sensor|MockObject
     */
    private $sensor;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var Event|MockObject
     */
    private $event;

    protected function setUp() : void
    {
        $this->factory = $this->getMockBuilder(Sensor\Factory::class)->setMockClassName('SensorFactoryMock001')->getMockForAbstractClass();
        $this->sensor = $this->getMockBuilder(Sensor::class)->setMockClassName('SensorMock001')->getMockForAbstractClass();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        $this->event = $this->getMockBuilder(Event::class)->setMockClassName('EventMock001')->getMockForAbstractClass();
    }

    public function testFactory()
    {
        $factory = new Factory($this->factory, $this->logger);
        $sensor = new LoggingSensor($this->sensor, $this->logger);

        $this->factory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->sensor)
        ;

        $this->assertEquals($sensor, $factory->create());
    }
}
