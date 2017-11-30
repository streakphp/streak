<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Entity\Comparison
 */
class ComparisonTest extends TestCase
{
    /**
     * @var Domain\AggregateRoot\Id|\PHPUnit_Framework_MockObject_MockObject
     */
    private $id1;

    /**
     * @var Domain\AggregateRoot\Id|\PHPUnit_Framework_MockObject_MockObject
     */
    private $id2;

    public function setUp()
    {
        $this->id1 = $this->getMockBuilder(Domain\AggregateRoot\Id::class)->getMockForAbstractClass();
        $this->id2 = $this->getMockBuilder(Domain\AggregateRoot\Id::class)->getMockForAbstractClass();
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
    }
}

namespace Streak\Domain\Entity\ComparisonTest;

use Streak\Domain;
use Streak\Domain\Entity;

class ComparisonStub implements Domain\Entity
{
    use Entity\Comparison;

    private $id;

    public function __construct(Entity\Id $id)
    {
        $this->id = $id;
    }

    public function id() : Entity\Id
    {
        return $this->id;
    }
}
