<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain\Aggregate;

use PHPUnit\Framework\TestCase;
use Streak\Domain;
use Streak\Domain\Aggregate;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Aggregate\Comparison
 */
class ComparisonTest extends TestCase
{
    /**
     * @var Aggregate\Id|\PHPUnit_Framework_MockObject_MockObject
     */
    private $id1;

    /**
     * @var Aggregate\Id|\PHPUnit_Framework_MockObject_MockObject
     */
    private $id2;

    /**
     * @var Aggregate\Id|\PHPUnit_Framework_MockObject_MockObject
     */
    private $id3;

    public function setUp()
    {
        $this->id1 = $this->getMockBuilder(Aggregate\Id::class)->getMockForAbstractClass();
        $this->id2 = $this->getMockBuilder(Aggregate\Id::class)->getMockForAbstractClass();
        $this->id3 = $this->getMockBuilder(Aggregate\Id::class)->getMockForAbstractClass();
    }

    public function testObject()
    {
        $this->id1
            ->expects($this->at(0))
            ->method('equals')
            ->with($this->id1)
            ->willReturn(true)
        ;

        $this->id2
            ->expects($this->at(0))
            ->method('equals')
            ->with($this->id1)
            ->willReturn(false)
        ;

        $this->id1
            ->expects($this->at(1))
            ->method('equals')
            ->with($this->id2)
            ->willReturn(false)
        ;

        $comparison1 = new ComparisonTest\ComparisonStub($this->id1);
        $comparison2 = new ComparisonTest\ComparisonStub($this->id2);

        $this->assertTrue($comparison1->equals($comparison1));
        $this->assertFalse($comparison1->equals($comparison2));
        $this->assertFalse($comparison2->equals($comparison1));

        /* @var $comparison3 Domain\Entity */
        $comparison3 = $this->getMockBuilder(Domain\Entity::class)->getMockForAbstractClass();
        $this->assertFalse($comparison1->equals($comparison3));
        $this->assertFalse($comparison2->equals($comparison3));

        $comparison4 = new ComparisonTest\NonAggregateComparisonStub($this->id3);
        $this->assertFalse($comparison1->equals($comparison4));
        $this->assertFalse($comparison2->equals($comparison4));
        $this->assertFalse($comparison4->equals($comparison1));
        $this->assertFalse($comparison4->equals($comparison2));
    }
}

namespace Streak\Domain\Aggregate\ComparisonTest;

use Streak\Domain;
use Streak\Domain\Entity;
use Streak\Domain\Aggregate;

class ComparisonStub implements Domain\Aggregate
{
    use Aggregate\Comparison;

    private $id;

    public function __construct(Aggregate\Id $id)
    {
        $this->id = $id;
    }

    public function aggregateId() : Aggregate\Id
    {
        return $this->id;
    }

    public function entityId() : Entity\Id
    {
        return $this->id;
    }

    public function id() : Domain\Id
    {
        return $this->id;
    }
}

class NonAggregateComparisonStub
{
    use Aggregate\Comparison;

    private $id;

    public function __construct(Aggregate\Id $id)
    {
        $this->id = $id;
    }

    public function aggregateId() : Aggregate\Id
    {
        return $this->id;
    }

    public function entityId() : Entity\Id
    {
        return $this->id;
    }
}
