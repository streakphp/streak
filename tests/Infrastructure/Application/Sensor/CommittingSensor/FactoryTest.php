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

namespace Streak\Infrastructure\Application\Sensor\CommittingSensor;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Application\Sensor;
use Streak\Infrastructure\Application\Sensor\CommittingSensor;
use Streak\Infrastructure\Domain\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Application\Sensor\CommittingSensor\Factory
 */
class FactoryTest extends TestCase
{
    private Sensor\Factory|MockObject $factory;

    private UnitOfWork|MockObject $uow;

    private Sensor|MockObject $sensor;

    protected function setUp(): void
    {
        $this->factory = $this->getMockBuilder(Sensor\Factory::class)->getMockForAbstractClass();
        $this->uow = $this->getMockBuilder(UnitOfWork::class)->getMockForAbstractClass();
        $this->sensor = $this->getMockBuilder(Sensor::class)->getMockForAbstractClass();
    }

    public function testFactory(): void
    {
        $factory = new Factory($this->factory, $this->uow);

        $this->factory
            ->expects(self::once())
            ->method('create')
            ->with()
            ->willReturn($this->sensor)
        ;

        $this->uow
            ->expects(self::once())
            ->method(self::anything())
        ;

        $expected = new CommittingSensor($this->sensor, $this->uow);

        $sensor = $factory->create();

        self::assertNotSame($expected, $sensor);
        self::assertEquals($expected, $sensor);
    }
}
