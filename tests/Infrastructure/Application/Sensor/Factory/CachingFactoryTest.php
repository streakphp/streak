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

namespace Streak\Infrastructure\Application\Sensor\Factory;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Application\Sensor;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Application\Sensor\Factory\CachingFactory
 */
class CachingFactoryTest extends TestCase
{
    private Sensor\Factory|MockObject $factory;
    private Sensor|MockObject $sensor;

    protected function setUp(): void
    {
        $this->factory = $this->getMockBuilder(Sensor\Factory::class)->getMockForAbstractClass();
        $this->sensor = $this->getMockBuilder(Sensor::class)->getMockForAbstractClass();
    }

    public function testFactory(): void
    {
        $factory = new CachingFactory($this->factory);

        $this->factory
            ->expects(self::once())
            ->method('create')
            ->with()
            ->willReturn($this->sensor)
        ;

        for ($i = 0; $i <= 10; ++$i) {
            $sensor = $factory->create();
            self::assertSame($this->sensor, $sensor);
        }
    }
}
