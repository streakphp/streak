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

namespace Streak\Infrastructure\UnitOfWork;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Infrastructure\AggregateRoot\Snapshotter;
use Streak\Infrastructure\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\UnitOfWork\SnapshottingUnitOfWork
 */
class SnapshottingUnitOfWorkTest extends TestCase
{
    private UnitOfWork $uow;

    private Snapshotter $snapshotter;

    private Event\Producer $producer;

    private Event\Sourced\AggregateRoot $aggregateRoot1;

    private Event\Sourced\AggregateRoot\Id $aggregateRootId1;

    private Event\Sourced\AggregateRoot $aggregateRoot2;

    private Event\Sourced\AggregateRoot\Id $aggregateRootId2;

    protected function setUp(): void
    {
        $this->uow = $this->getMockBuilder(UnitOfWork::class)->getMockForAbstractClass();
        $this->snapshotter = $this->getMockBuilder(Snapshotter::class)->getMockForAbstractClass();
        $this->producer = $this->getMockBuilder(Event\Producer::class)->setMockClassName('s__producer')->getMockForAbstractClass();
        $this->aggregateRoot1 = $this->getMockBuilder(Event\Sourced\AggregateRoot::class)->setMockClassName('s__ar1')->getMockForAbstractClass();
        $this->aggregateRootId1 = $this->getMockBuilder(Event\Sourced\AggregateRoot\Id::class)->getMockForAbstractClass();
        $this->aggregateRoot2 = $this->getMockBuilder(Event\Sourced\AggregateRoot::class)->setMockClassName('s__ar2')->getMockForAbstractClass();
        $this->aggregateRootId2 = $this->getMockBuilder(Event\Sourced\AggregateRoot\Id::class)->getMockForAbstractClass();
    }

    public function testObject(): void
    {
        $uow = new SnapshottingUnitOfWork($this->uow, $this->snapshotter);

        $this->producer
            ->expects(self::never()) // we do not handle this type of object
            ->method('producerId')
        ;

        $this->aggregateRoot1
            ->expects(self::atLeastOnce())
            ->method('producerId')
            ->willReturn($this->aggregateRootId1)
        ;

        $this->aggregateRoot1
            ->expects(self::exactly(3))
            ->method('version')
            ->willReturnOnConsecutiveCalls(
                30,
                30,
                30
            )
        ;

        $this->aggregateRoot2
            ->expects(self::atLeastOnce())
            ->method('producerId')
            ->willReturn($this->aggregateRootId2)
        ;

        $this->aggregateRoot2
            ->expects(self::exactly(2))
            ->method('version')
            ->willReturnOnConsecutiveCalls(
                40,
                50
            )
        ;

        $this->uow
            ->expects(self::exactly(8))
            ->method('has')
            ->withConsecutive(
                [$this->producer],
                [$this->aggregateRoot1]
            )->willReturnOnConsecutiveCalls(
                false,
                false,
                true,
                false,
                true,
                true,
                false,
                false
            );

        $this->uow
            ->expects(self::exactly(4))
            ->method('count')
            ->willReturnOnConsecutiveCalls(
                0,
                1,
                2,
                0
            )
        ;

        $this->uow
            ->expects(self::atLeastOnce())
            ->method('add')
            ->withConsecutive(
                [$this->producer],
                [$this->aggregateRoot1],
                [$this->producer],
                [$this->aggregateRoot1],
                [$this->aggregateRoot2]
            )
        ;

        $this->uow
            ->expects(self::atLeastOnce())
            ->method('remove')
            ->withConsecutive(
                [$this->producer],
                [$this->aggregateRoot1]
            )
        ;

        $this->uow
            ->expects(self::atLeastOnce())
            ->method('clear')
        ;

        self::assertFalse($uow->has($this->producer));
        self::assertFalse($uow->has($this->aggregateRoot1));
        self::assertSame(0, $uow->count());

        $uow->add($this->producer);

        self::assertTrue($uow->has($this->producer));
        self::assertFalse($uow->has($this->aggregateRoot1));
        self::assertSame(1, $uow->count());

        $uow->add($this->aggregateRoot1);

        self::assertTrue($uow->has($this->producer));
        self::assertTrue($uow->has($this->aggregateRoot1));
        self::assertSame(2, $uow->count());

        $uow->remove($this->producer);
        $uow->remove($this->aggregateRoot1);

        self::assertFalse($uow->has($this->producer));
        self::assertFalse($uow->has($this->aggregateRoot1));
        self::assertSame(0, $uow->count());

        $uow->clear();

        $uow->add($this->producer);
        $uow->add($this->aggregateRoot1);
        $uow->add($this->aggregateRoot2);

        $this->uow
            ->expects(self::once())
            ->method('commit')
            ->with()
            ->willReturnCallback(function () {
                yield $this->producer;
                yield $this->aggregateRoot1;
                yield $this->aggregateRoot2;
            })
        ;

        $this->snapshotter
            ->expects(self::once())
            ->method('takeSnapshot')
            ->with($this->aggregateRoot2)
        ;

        $committed = iterator_to_array($uow->commit());

        self::assertSame([$this->producer, $this->aggregateRoot1, $this->aggregateRoot2], $committed);

        $this->uow
            ->expects(self::exactly(3))
            ->method('uncommitted')
            ->willReturnOnConsecutiveCalls(
                [],
                [$this->aggregateRoot1],
                [$this->aggregateRoot1, $this->aggregateRoot2],
                [$this->aggregateRoot1]
            )
        ;

        self::assertEmpty($uow->uncommitted());
        self::assertSame([$this->aggregateRoot1], $uow->uncommitted());
        self::assertSame([$this->aggregateRoot1, $this->aggregateRoot2], $uow->uncommitted());
    }

    /**
     * @dataProvider commitIntervalProvider
     */
    public function testCommitInterval(int $beforeVersion, int $afterVersion, int $snapshotInterval, bool $snapshotTaken): void
    {
        $this->uow
            ->expects(self::once())
            ->method('commit')
            ->with()
            ->willReturnCallback(function () {
                yield $this->aggregateRoot1;
            })
        ;

        $snapshotter = $this->getMockBuilder(Snapshotter::class)->getMock();
        $this->aggregateRoot1
            ->expects(self::exactly(2))
            ->method('version')
            ->willReturnOnConsecutiveCalls(
                $beforeVersion,
                $afterVersion
            )
        ;

        $this->aggregateRoot1
            ->expects(self::atLeastOnce())
            ->method('producerId')
            ->willReturn($this->aggregateRootId1)
        ;

        if ($snapshotTaken) {
            $snapshotter->expects(self::once())->method('takeSnapshot');
        } else {
            $snapshotter->expects(self::never())->method('takeSnapshot');
        }

        $uow = new SnapshottingUnitOfWork($this->uow, $snapshotter, $snapshotInterval);

        $uow->add($this->aggregateRoot1);
        iterator_to_array($uow->commit());
    }

    public function commitIntervalProvider(): array
    {
        return [
            [1, 2, 3, false],
            [1, 2, 2, true],
            [1, 2, 3, false],
            [1, 2, 1, true],
            [1, 10, 5, true],
            [10, 16, 5, true],
            [16, 24, 5, true],
            [100, 200, 50, true],
            [200, 300, 101, true],
            [100, 200, 201, false],
            [131, 133, 2, true],
            [1033, 1043, 8, true],
        ];
    }

    /**
     * @dataProvider wrongIntervalProvider
     */
    public function testItDoesNotCreateWithWrongInterval(int $interval): void
    {
        self::expectException(InvalidArgumentException::class);
        new SnapshottingUnitOfWork($this->uow, $this->snapshotter, 0);
    }

    public function wrongIntervalProvider()
    {
        return [
            [0],
            [-1],
        ];
    }
}
