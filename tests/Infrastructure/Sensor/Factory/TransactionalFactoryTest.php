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

namespace Streak\Infrastructure\Sensor\Factory;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Application\Sensor;
use Streak\Infrastructure\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Sensor\Factory\TransactionalFactory
 */
class TransactionalFactoryTest extends TestCase
{
    /**
     * @var MockObject|Sensor\Factory
     */
    private $factory;

    /**
     * @var MockObject|UnitOfWork
     */
    private $uow;

    /**
     * @var MockObject|Sensor
     */
    private $sensor;

    protected function setUp(): void
    {
        $this->factory = $this->getMockBuilder(Sensor\Factory::class)->getMockForAbstractClass();
        $this->uow = $this->getMockBuilder(UnitOfWork::class)->getMockForAbstractClass();
        $this->sensor = $this->getMockBuilder(Sensor::class)->getMockForAbstractClass();
    }

    public function testFactory(): void
    {
        $factory = new TransactionalFactory($this->factory, $this->uow);

        $this->factory
            ->expects(self::once())
            ->method('create')
            ->with()
            ->willReturn($this->sensor)
        ;

        $this->uow
            ->expects(self::once())
            ->method('add')
            ->with($this->sensor)
        ;

        $sensor = $factory->create();

        self::assertSame($this->sensor, $sensor);
    }
}
