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
     * @var Sensor\Factory|MockObject
     */
    private $factory;

    /**
     * @var UnitOfWork|MockObject
     */
    private $uow;

    /**
     * @var Sensor|MockObject
     */
    private $sensor;

    protected function setUp() : void
    {
        $this->factory = $this->getMockBuilder(Sensor\Factory::class)->getMockForAbstractClass();
        $this->uow = $this->getMockBuilder(UnitOfWork::class)->getMockForAbstractClass();
        $this->sensor = $this->getMockBuilder(Sensor::class)->getMockForAbstractClass();
    }

    public function testFactory()
    {
        $factory = new TransactionalFactory($this->factory, $this->uow);

        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with()
            ->willReturn($this->sensor)
        ;

        $this->uow
            ->expects($this->once())
            ->method('add')
            ->with($this->sensor)
        ;

        $sensor = $factory->create();

        $this->assertSame($this->sensor, $sensor);
    }
}
