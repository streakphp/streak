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

namespace Streak\Infrastructure\AggregateRoot\Snapshotter\Storage;

use PHPUnit\Framework\MockObject\MockObject;
use Streak\Domain\AggregateRoot;
use Streak\Infrastructure\AggregateRoot\Snapshotter\Storage;
use Streak\Infrastructure\AggregateRoot\Snapshotter\Storage\Exception\SnapshotNotFound;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Storage
     */
    protected $storage;

    /**
     * @var AggregateRoot|MockObject
     */
    protected $aggregate1;

    /**
     * @var AggregateRoot\Id|MockObject
     */
    protected $aggregateId1;

    /**
     * @var AggregateRoot|MockObject
     */
    protected $aggregate2;

    /**
     * @var AggregateRoot\Id|MockObject
     */
    protected $aggregateId2;

    protected function setUp()
    {
        $this->storage = $this->newStorage();

        $this->aggregateId1 = new Storage\StorageTestCase\ExtendedUUID1('9bf583d5-d4ff-4cf3-bc53-8ffb6be0c67b');
        $this->aggregate1 = $this->getMockBuilder(AggregateRoot::class)->setMockClassName('streak__aggregate_1')->getMockForAbstractClass();
        $this->aggregate1->expects($this->any())->method('aggregateRootId')->with()->willReturn($this->aggregateId1);

        $this->aggregateId2 = new Storage\StorageTestCase\ExtendedUUID2('d61546c6-cc51-4584-90f8-34203fd79b41');
        $this->aggregate2 = $this->getMockBuilder(AggregateRoot::class)->setMockClassName('streak__aggregate_2')->getMockForAbstractClass();
        $this->aggregate2->expects($this->any())->method('aggregateRootId')->with()->willReturn($this->aggregateId2);
    }

    public function testObject()
    {
        $this->storage->store($this->aggregate1, 'snapshot-1');

        $result = $this->storage->find($this->aggregate1);

        $this->assertSame('snapshot-1', $result);

        $result = $this->storage->find($this->aggregate1);

        $this->assertSame('snapshot-1', $result);

        $this->storage->store($this->aggregate1, 'snapshot-2');

        $result = $this->storage->find($this->aggregate1);

        $this->assertSame('snapshot-2', $result);

        $result = $this->storage->find($this->aggregate1);

        $this->assertSame('snapshot-2', $result);

        $this->expectExceptionObject(new SnapshotNotFound($this->aggregate2));

        $this->storage->find($this->aggregate2);
    }

    abstract protected function newStorage() : Storage;
}

namespace Streak\Infrastructure\AggregateRoot\Snapshotter\Storage\StorageTestCase;

use Streak\Domain\AggregateRoot;
use Streak\Domain\Id\UUID;

class ExtendedUUID1 extends UUID implements AggregateRoot\Id
{
}

class ExtendedUUID2 extends UUID implements AggregateRoot\Id
{
}
