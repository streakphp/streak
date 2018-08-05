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
use Streak\Domain\AggregateRoot;
use Streak\Infrastructure\AggregateRoot\Snapshotter;
use Streak\Infrastructure\AggregateRoot\Snapshotter\Storage\Exception\SnapshotNotFound;
use Streak\Infrastructure\Serializer;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\AggregateRoot\Snapshotter\SnapshottableAggregatesSnapshotter
 */
class SnapshottableAggregatesSnapshotterTest extends TestCase
{
    /**
     * @var Serializer|MockObject
     */
    private $serializer;
    /**
     * @var Snapshotter\Storage|MockObject
     */
    private $storage;

    /**
     * @var AggregateRoot|MockObject
     */
    private $nonSnapshottableAggregateRoot;

    /**
     * @var AggregateRoot|\Serializable|MockObject
     */
    private $snapshottableAggregateRoot;

    protected function setUp()
    {
        $this->serializer = $this->getMockBuilder(Serializer::class)->getMockForAbstractClass();
        $this->storage = $this->getMockBuilder(Snapshotter\Storage::class)->getMockForAbstractClass();
        $this->nonSnapshottableAggregateRoot = $this->getMockBuilder(AggregateRoot::class)->getMockForAbstractClass();
        $this->snapshottableAggregateRoot = $this->getMockBuilder([AggregateRoot::class, AggregateRoot\Snapshottable::class])->getMock();
    }

    public function testTakingSnapshot()
    {
        $this->storage
            ->expects($this->never())
            ->method('find')
        ;

        $snapshotter = new SnapshottableAggregatesSnapshotter($this->serializer, $this->storage);

        $result = $snapshotter->takeSnapshot($this->nonSnapshottableAggregateRoot);

        $this->assertSame($this->nonSnapshottableAggregateRoot, $result);

        $this->snapshottableAggregateRoot
            ->expects($this->once())
            ->method('toMemento')
            ->willReturn(['memento' => 'this'])
        ;

        $this->serializer
            ->expects($this->once())
            ->method('serialize')
            ->with(['memento' => 'this'])
            ->willReturn('serialized')
        ;

        $this->storage
            ->expects($this->once())
            ->method('store')
            ->with($this->snapshottableAggregateRoot, 'serialized')
        ;

        $result = $snapshotter->takeSnapshot($this->snapshottableAggregateRoot);

        $this->assertSame($this->snapshottableAggregateRoot, $result);
    }

    public function testRestoringWhenSnapshotFound()
    {
        $this->storage
            ->expects($this->never())
            ->method('store')
        ;

        $snapshotter = new SnapshottableAggregatesSnapshotter($this->serializer, $this->storage);

        $result = $snapshotter->restoreToSnapshot($this->nonSnapshottableAggregateRoot);

        $this->assertSame($this->nonSnapshottableAggregateRoot, $result);

        $this->storage
            ->expects($this->at(0))
            ->method('find')
            ->with($this->snapshottableAggregateRoot)
            ->willReturn('serialized')
        ;

        $this->serializer
            ->expects($this->once())
            ->method('unserialize')
            ->with('serialized')
            ->willReturn(['unserialize' => 'this'])
        ;

        $this->snapshottableAggregateRoot
            ->expects($this->once())
            ->method('fromMemento')
            ->with(['unserialize' => 'this'])
        ;

        $result = $snapshotter->restoreToSnapshot($this->snapshottableAggregateRoot);

        $this->assertSame($this->snapshottableAggregateRoot, $result);
    }

    public function testRestoringWhenSnapshotNotFound()
    {
        $snapshotter = new SnapshottableAggregatesSnapshotter($this->serializer, $this->storage);

        $this->storage
            ->expects($this->at(0))
            ->method('find')
            ->with($this->snapshottableAggregateRoot)
            ->willThrowException(new SnapshotNotFound($this->snapshottableAggregateRoot))
        ;

        $this->serializer
            ->expects($this->never())
            ->method($this->anything())
        ;

        $this->snapshottableAggregateRoot
            ->expects($this->never())
            ->method('fromMemento')
        ;

        $result = $snapshotter->restoreToSnapshot($this->snapshottableAggregateRoot);

        $this->assertSame($this->snapshottableAggregateRoot, $result);
    }
}
