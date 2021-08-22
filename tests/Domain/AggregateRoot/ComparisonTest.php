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

namespace Streak\Domain\AggregateRoot;

use PHPUnit\Framework\TestCase;
use Streak\Domain;
use Streak\Domain\AggregateRoot;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\AggregateRoot\Comparison
 */
class ComparisonTest extends TestCase
{
    private AggregateRoot\Id $id1;
    private AggregateRoot\Id $id2;
    private AggregateRoot\Id $id3;

    protected function setUp(): void
    {
        $this->id1 = $this->getMockBuilder(AggregateRoot\Id::class)->getMockForAbstractClass();
        $this->id2 = $this->getMockBuilder(AggregateRoot\Id::class)->getMockForAbstractClass();
        $this->id3 = $this->getMockBuilder(AggregateRoot\Id::class)->getMockForAbstractClass();
    }

    public function testObject(): void
    {
        $this->id1
            ->expects(self::at(0))
            ->method('equals')
            ->with($this->id1)
            ->willReturn(true)
        ;

        $this->id2
            ->expects(self::at(0))
            ->method('equals')
            ->with($this->id1)
            ->willReturn(false)
        ;

        $this->id1
            ->expects(self::at(1))
            ->method('equals')
            ->with($this->id2)
            ->willReturn(false)
        ;

        $comparison1 = new ComparisonTest\ComparisonStub($this->id1);
        $comparison2 = new ComparisonTest\ComparisonStub($this->id2);

        self::assertTrue($comparison1->equals($comparison1));
        self::assertFalse($comparison1->equals($comparison2));
        self::assertFalse($comparison2->equals($comparison1));

        $comparison3 = $this->getMockBuilder(Domain\Entity::class)->getMockForAbstractClass();
        self::assertFalse($comparison1->equals($comparison3));
        self::assertFalse($comparison2->equals($comparison3));

        $comparison4 = new ComparisonTest\NonAggregateRootComparisonStub($this->id3);
        self::assertFalse($comparison1->equals($comparison4));
        self::assertFalse($comparison2->equals($comparison4));
        self::assertFalse($comparison4->equals($comparison1));
        self::assertFalse($comparison4->equals($comparison2));
    }
}

namespace Streak\Domain\AggregateRoot\ComparisonTest;

use Streak\Domain;
use Streak\Domain\AggregateRoot;

class ComparisonStub implements Domain\AggregateRoot
{
    use AggregateRoot\Comparison;

    private AggregateRoot\Id $id;

    public function __construct(AggregateRoot\Id $id)
    {
        $this->id = $id;
    }

    public function id(): AggregateRoot\Id
    {
        return $this->id;
    }
}

class NonAggregateRootComparisonStub
{
    use AggregateRoot\Comparison;

    private AggregateRoot\Id $id;

    public function __construct(AggregateRoot\Id $id)
    {
        $this->id = $id;
    }

    public function id(): AggregateRoot\Id
    {
        return $this->id;
    }
}
