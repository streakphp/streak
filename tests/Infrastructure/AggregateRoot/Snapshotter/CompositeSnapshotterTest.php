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

namespace Streak\Infrastructure\AggregateRoot\Snapshotter;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain;
use Streak\Infrastructure\AggregateRoot;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\AggregateRoot\Snapshotter\CompositeSnapshotter
 */
class CompositeSnapshotterTest extends TestCase
{
    /**
     * @var AggregateRoot\Snapshotter|MockObject
     */
    private $snapshotter1;

    /**
     * @var AggregateRoot\Snapshotter|MockObject
     */
    private $snapshotter2;

    /**
     * @var Domain\AggregateRoot|MockObject
     */
    private $aggregate1;

    /**
     * @var Domain\AggregateRoot|MockObject
     */
    private $aggregate2;

    /**
     * @var Domain\AggregateRoot|MockObject
     */
    private $aggregate3;

    protected function setUp()
    {
        $this->snapshotter1 = $this->getMockBuilder(AggregateRoot\Snapshotter::class)->getMockForAbstractClass();
        $this->snapshotter2 = $this->getMockBuilder(AggregateRoot\Snapshotter::class)->getMockForAbstractClass();

        $this->aggregate1 = $this->getMockBuilder(Domain\AggregateRoot::class)->getMockForAbstractClass();
        $this->aggregate2 = $this->getMockBuilder(Domain\AggregateRoot::class)->getMockForAbstractClass();
        $this->aggregate3 = $this->getMockBuilder(Domain\AggregateRoot::class)->getMockForAbstractClass();
    }

    public function testTakingSnapshotWhenThereAreNoSnapshotters()
    {
        $snapshotter = new CompositeSnapshotter();

        $result = $snapshotter->takeSnapshot($this->aggregate1);

        $this->assertSame($result, $this->aggregate1);
    }

    public function testTakingSnapshotWithAllSnapshotters()
    {
        $snapshotter = new CompositeSnapshotter($this->snapshotter1, $this->snapshotter2);

        $this->snapshotter1
            ->expects($this->once())
            ->method('takeSnapshot')
            ->with($this->aggregate1)
            ->willReturn($this->aggregate2)
        ;

        $this->snapshotter2
            ->expects($this->once())
            ->method('takeSnapshot')
            ->with($this->aggregate2)
            ->willReturn($this->aggregate3)
        ;

        $result = $snapshotter->takeSnapshot($this->aggregate1);

        $this->assertSame($result, $this->aggregate3);
    }

    public function testTakingSnapshotWithOneSnapshotter()
    {
        $snapshotter = new CompositeSnapshotter($this->snapshotter1, $this->snapshotter2);

        $this->snapshotter1
            ->expects($this->once())
            ->method('takeSnapshot')
            ->with($this->aggregate1)
            ->willReturn($this->aggregate1)
        ;

        $this->snapshotter2
            ->expects($this->once())
            ->method('takeSnapshot')
            ->with($this->aggregate1)
            ->willReturn($this->aggregate2)
        ;

        $result = $snapshotter->takeSnapshot($this->aggregate1);

        $this->assertSame($result, $this->aggregate2);
    }

    public function testRestoringWhenThereAreNoSnapshotters()
    {
        $snapshotter = new CompositeSnapshotter();

        $result = $snapshotter->restoreToSnapshot($this->aggregate1);

        $this->assertSame($result, $this->aggregate1);
    }

    public function testRestoringWithAllSnapshotters()
    {
        $snapshotter = new CompositeSnapshotter($this->snapshotter1, $this->snapshotter2);

        $this->snapshotter1
            ->expects($this->once())
            ->method('restoreToSnapshot')
            ->with($this->aggregate1)
            ->willReturn($this->aggregate2)
        ;

        $this->snapshotter2
            ->expects($this->once())
            ->method('restoreToSnapshot')
            ->with($this->aggregate2)
            ->willReturn($this->aggregate3)
        ;

        $result = $snapshotter->restoreToSnapshot($this->aggregate1);

        $this->assertSame($result, $this->aggregate3);
    }

    public function testRestoringWithOneSnapshotter()
    {
        $snapshotter = new CompositeSnapshotter($this->snapshotter1, $this->snapshotter2);

        $this->snapshotter1
            ->expects($this->once())
            ->method('restoreToSnapshot')
            ->with($this->aggregate1)
            ->willReturn($this->aggregate1)
        ;

        $this->snapshotter2
            ->expects($this->once())
            ->method('restoreToSnapshot')
            ->with($this->aggregate1)
            ->willReturn($this->aggregate2)
        ;

        $result = $snapshotter->restoreToSnapshot($this->aggregate1);

        $this->assertSame($result, $this->aggregate2);
    }
}
