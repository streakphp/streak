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

namespace Streak\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Streak\Domain;
use Streak\Domain\Entity;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Entity\Comparison
 */
class ComparisonTest extends TestCase
{
    private Entity\Id $id1;
    private Entity\Id $id2;
    private Entity\Id $id3;

    protected function setUp(): void
    {
        $this->id1 = $this->getMockBuilder(Entity\Id::class)->getMockForAbstractClass();
        $this->id2 = $this->getMockBuilder(Entity\Id::class)->getMockForAbstractClass();
        $this->id3 = $this->getMockBuilder(Entity\Id::class)->getMockForAbstractClass();
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

        $comparison4 = new ComparisonTest\NonEntityComparisonStub($this->id3);
        self::assertFalse($comparison1->equals($comparison4));
        self::assertFalse($comparison2->equals($comparison4));
        self::assertFalse($comparison4->equals($comparison1));
        self::assertFalse($comparison4->equals($comparison2));
    }
}

namespace Streak\Domain\Entity\ComparisonTest;

use Streak\Domain;
use Streak\Domain\Entity;

class ComparisonStub implements Domain\Entity
{
    use Entity\Comparison;

    public function __construct(private Entity\Id $id)
    {
    }

    public function id(): Entity\Id
    {
        return $this->id;
    }
}

class NonEntityComparisonStub
{
    use Entity\Comparison;

    public function __construct(private Entity\Id $id)
    {
    }

    public function id(): Entity\Id
    {
        return $this->id;
    }
}
