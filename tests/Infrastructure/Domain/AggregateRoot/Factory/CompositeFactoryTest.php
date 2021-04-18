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

namespace Streak\Infrastructure\Domain\AggregateRoot\Factory;

use PHPUnit\Framework\TestCase;
use Streak\Domain\AggregateRoot;
use Streak\Domain\Exception\InvalidAggregateIdGiven;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\AggregateRoot\Factory\CompositeFactory
 */
class CompositeFactoryTest extends TestCase
{
    private AggregateRoot\Id $id1;

    private AggregateRoot\Factory $factory1;
    private AggregateRoot\Factory $factory2;

    private AggregateRoot $aggregate1;

    protected function setUp(): void
    {
        $this->id1 = $this->getMockBuilder(AggregateRoot\Id::class)->getMockForAbstractClass();
        $this->factory1 = $this->getMockBuilder(AggregateRoot\Factory::class)->getMockForAbstractClass();
        $this->factory2 = $this->getMockBuilder(AggregateRoot\Factory::class)->getMockForAbstractClass();
        $this->aggregate1 = $this->getMockBuilder(AggregateRoot::class)->getMockForAbstractClass();
    }

    public function testEmptyComposite(): void
    {
        $composite = new CompositeFactory();

        $this->expectExceptionObject(new InvalidAggregateIdGiven($this->id1));

        $composite->create($this->id1);
    }

    public function testComposite(): void
    {
        $composite = new CompositeFactory();
        $composite->add($this->factory1);
        $composite->add($this->factory2);

        $this->factory1
            ->expects(self::once())
            ->method('create')
            ->with($this->id1)
            ->willThrowException(new InvalidAggregateIdGiven($this->id1))
        ;

        $this->factory2
            ->expects(self::once())
            ->method('create')
            ->with($this->id1)
            ->willReturn($this->aggregate1)
        ;

        $aggregate = $composite->create($this->id1);

        self::assertSame($this->aggregate1, $aggregate);
    }
}
