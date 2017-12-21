<?php

/*
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
    /**
     * @var AggregateRoot\Id|\PHPUnit_Framework_MockObject_MockObject
     */
    private $id1;

    /**
     * @var AggregateRoot\Id|\PHPUnit_Framework_MockObject_MockObject
     */
    private $id2;

    /**
     * @var AggregateRoot\Id|\PHPUnit_Framework_MockObject_MockObject
     */
    private $id3;

    public function setUp()
    {
        $this->id1 = $this->getMockBuilder(AggregateRoot\Id::class)->getMockForAbstractClass();
        $this->id2 = $this->getMockBuilder(AggregateRoot\Id::class)->getMockForAbstractClass();
        $this->id3 = $this->getMockBuilder(AggregateRoot\Id::class)->getMockForAbstractClass();
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

        $comparison4 = new ComparisonTest\NonAggregateRootComparisonStub($this->id3);
        $this->assertFalse($comparison1->equals($comparison4));
        $this->assertFalse($comparison2->equals($comparison4));
        $this->assertFalse($comparison4->equals($comparison1));
        $this->assertFalse($comparison4->equals($comparison2));
    }
}

namespace Streak\Domain\AggregateRoot\ComparisonTest;

use Streak\Domain;
use Streak\Domain\Aggregate;
use Streak\Domain\AggregateRoot;
use Streak\Domain\Entity;

class ComparisonStub implements Domain\AggregateRoot
{
    use AggregateRoot\Comparison;

    private $id;

    public function __construct(AggregateRoot\Id $id)
    {
        $this->id = $id;
    }

    public function aggregateRootId() : AggregateRoot\Id
    {
        return $this->id;
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

class NonAggregateRootComparisonStub
{
    use AggregateRoot\Comparison;

    private $id;

    public function __construct(AggregateRoot\Id $id)
    {
        $this->id = $id;
    }

    public function aggregateRootId() : AggregateRoot\Id
    {
        return $this->id;
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
