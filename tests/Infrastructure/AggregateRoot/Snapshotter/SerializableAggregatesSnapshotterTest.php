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

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\AggregateRoot\Snapshotter\SerializableAggregatesSnapshotter
 */
class SerializableAggregatesSnapshotterTest extends TestCase
{
    /**
     * @var Snapshotter\Storage|MockObject
     */
    private $storage;

    /**
     * @var AggregateRoot|MockObject
     */
    private $nonSerializableAggregateRoot;

    /**
     * @var AggregateRoot|\Serializable|MockObject
     */
    private $serializableAggregateRoot;

    protected function setUp()
    {
        $this->storage = $this->getMockBuilder(Snapshotter\Storage::class)->getMockForAbstractClass();
        $this->nonSerializableAggregateRoot = $this->getMockBuilder(AggregateRoot::class)->getMockForAbstractClass();
        $this->serializableAggregateRoot = $this->getMockBuilder([AggregateRoot::class, \Serializable::class])->getMock();
    }

    public function testTakingSnapshot()
    {
        $this->storage
            ->expects($this->never())
            ->method('find')
        ;

        $snapshotter = new SerializableAggregatesSnapshotter($this->storage);

        $result = $snapshotter->takeSnapshot($this->nonSerializableAggregateRoot);

        $this->assertSame($this->nonSerializableAggregateRoot, $result);

        $this->serializableAggregateRoot
            ->expects($this->once())
            ->method('serialize')
            ->willReturn('serialized')
        ;

        $this->storage
            ->expects($this->once())
            ->method('store')
            ->with($this->serializableAggregateRoot, 'serialized')
        ;

        $result = $snapshotter->takeSnapshot($this->serializableAggregateRoot);

        $this->assertSame($this->serializableAggregateRoot, $result);
    }

    public function testRestoringWhenSnapshotFound()
    {
        $this->storage
            ->expects($this->never())
            ->method('store')
        ;

        $snapshotter = new SerializableAggregatesSnapshotter($this->storage);

        $result = $snapshotter->restoreToSnapshot($this->nonSerializableAggregateRoot);

        $this->assertSame($this->nonSerializableAggregateRoot, $result);

        $this->storage
            ->expects($this->at(0))
            ->method('find')
            ->with($this->serializableAggregateRoot)
            ->willReturn('serialized')
        ;

        $this->serializableAggregateRoot
            ->expects($this->once())
            ->method('unserialize')
            ->with('serialized')
        ;

        $result = $snapshotter->restoreToSnapshot($this->serializableAggregateRoot);

        $this->assertSame($this->serializableAggregateRoot, $result);
    }

    public function testRestoringWhenSnapshotNotFound()
    {
        $snapshotter = new SerializableAggregatesSnapshotter($this->storage);

        $this->storage
            ->expects($this->at(0))
            ->method('find')
            ->with($this->serializableAggregateRoot)
            ->willThrowException(new SnapshotNotFound($this->serializableAggregateRoot))
        ;

        $this->serializableAggregateRoot
            ->expects($this->never())
            ->method($this->anything())
        ;

        $result = $snapshotter->restoreToSnapshot($this->serializableAggregateRoot);

        $this->assertSame($this->serializableAggregateRoot, $result);
    }
}
