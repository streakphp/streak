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

namespace Streak\Infrastructure\Domain\AggregateRoot\Snapshotter;

use PHPUnit\Framework\TestCase;
use Streak\Domain\AggregateRoot;
use Streak\Infrastructure\Domain\AggregateRoot\Snapshotter;
use Streak\Infrastructure\Domain\AggregateRoot\Snapshotter\SnapshottableAggregatesSnapshotterTest\SnapshottableAggregateRoot;
use Streak\Infrastructure\Domain\AggregateRoot\Snapshotter\Storage\Exception\SnapshotNotFound;
use Streak\Infrastructure\Domain\Serializer;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\AggregateRoot\Snapshotter\SnapshottableAggregatesSnapshotter
 */
class SnapshottableAggregatesSnapshotterTest extends TestCase
{
    private Serializer $serializer;

    private Snapshotter\Storage $storage;

    private AggregateRoot $nonSnapshottableAggregateRoot;

    private SnapshottableAggregateRoot $snapshottableAggregateRoot;

    protected function setUp(): void
    {
        $this->serializer = $this->getMockBuilder(Serializer::class)->getMockForAbstractClass();
        $this->storage = $this->getMockBuilder(Snapshotter\Storage::class)->getMockForAbstractClass();
        $this->nonSnapshottableAggregateRoot = $this->getMockBuilder(AggregateRoot::class)->getMockForAbstractClass();
        $this->snapshottableAggregateRoot = $this->getMockBuilder(SnapshottableAggregateRoot::class)->getMock();
    }

    public function testTakingSnapshotOfSnapshottableAggregateRoot(): void
    {
        $this->storage
            ->expects(self::never())
            ->method('find')
        ;

        $this->snapshottableAggregateRoot
            ->expects(self::once())
            ->method('toMemento')
            ->willReturn(['memento' => 'this'])
        ;

        $this->serializer
            ->expects(self::once())
            ->method('serialize')
            ->with(['memento' => 'this'])
            ->willReturn('serialized')
        ;

        $this->storage
            ->expects(self::once())
            ->method('store')
            ->with($this->snapshottableAggregateRoot, 'serialized')
        ;

        $snapshotter = new SnapshottableAggregatesSnapshotter($this->serializer, $this->storage);
        $snapshotter->takeSnapshot($this->snapshottableAggregateRoot);
    }

    public function testTakingSnapshotOfNonSnapshottableAggregateRoot(): void
    {
        $this->storage
            ->expects(self::never())
            ->method('find')
        ;

        $this->snapshottableAggregateRoot
            ->expects(self::never())
            ->method('toMemento')
        ;

        $this->serializer
            ->expects(self::never())
            ->method('serialize')
        ;

        $this->storage
            ->expects(self::never())
            ->method('store')
        ;

        $snapshotter = new SnapshottableAggregatesSnapshotter($this->serializer, $this->storage);
        $snapshotter->takeSnapshot($this->nonSnapshottableAggregateRoot);
    }

    public function testRestoringWhenSnapshotFound(): void
    {
        $this->storage
            ->expects(self::never())
            ->method('store')
        ;

        $snapshotter = new SnapshottableAggregatesSnapshotter($this->serializer, $this->storage);

        self::assertNull($snapshotter->restoreToSnapshot($this->nonSnapshottableAggregateRoot));

        $this->storage
            ->expects(self::at(0))
            ->method('find')
            ->with($this->snapshottableAggregateRoot)
            ->willReturn('serialized')
        ;

        $this->serializer
            ->expects(self::once())
            ->method('unserialize')
            ->with('serialized')
            ->willReturn(['unserialize' => 'this'])
        ;

        $this->snapshottableAggregateRoot
            ->expects(self::once())
            ->method('fromMemento')
            ->with(['unserialize' => 'this'])
        ;

        $result = $snapshotter->restoreToSnapshot($this->snapshottableAggregateRoot);

        self::assertSame($this->snapshottableAggregateRoot, $result);
    }

    public function testRestoringWhenSnapshotNotFound(): void
    {
        $snapshotter = new SnapshottableAggregatesSnapshotter($this->serializer, $this->storage);

        $this->storage
            ->expects(self::at(0))
            ->method('find')
            ->with($this->snapshottableAggregateRoot)
            ->willThrowException(new SnapshotNotFound($this->snapshottableAggregateRoot))
        ;

        $this->serializer
            ->expects(self::never())
            ->method(self::anything())
        ;

        $this->snapshottableAggregateRoot
            ->expects(self::never())
            ->method('fromMemento')
        ;

        self::assertNull($snapshotter->restoreToSnapshot($this->snapshottableAggregateRoot));
    }
}

namespace Streak\Infrastructure\Domain\AggregateRoot\Snapshotter\SnapshottableAggregatesSnapshotterTest;

use Streak\Domain\AggregateRoot;

abstract class SnapshottableAggregateRoot implements AggregateRoot, AggregateRoot\Snapshottable
{
}
