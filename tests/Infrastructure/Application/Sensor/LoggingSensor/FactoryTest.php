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

namespace Streak\Infrastructure\Application\Sensor\LoggingSensor;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Streak\Application\Sensor;
use Streak\Infrastructure\Application\Sensor\LoggingSensor;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Application\Sensor\LoggingSensor\Factory
 */
class FactoryTest extends TestCase
{
    private Sensor\Factory|MockObject $factory;

    private Sensor|MockObject $sensor;

    private LoggerInterface|MockObject $logger;

    protected function setUp(): void
    {
        $this->factory = $this->getMockBuilder(Sensor\Factory::class)->setMockClassName('SensorFactoryMock001')->getMockForAbstractClass();
        $this->sensor = $this->getMockBuilder(Sensor::class)->setMockClassName('SensorMock001')->getMockForAbstractClass();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
    }

    public function testFactory(): void
    {
        $factory = new Factory($this->factory, $this->logger);
        $sensor = new LoggingSensor($this->sensor, $this->logger);

        $this->factory
            ->expects(self::once())
            ->method('create')
            ->willReturn($this->sensor)
        ;

        self::assertEquals($sensor, $factory->create());
    }
}
