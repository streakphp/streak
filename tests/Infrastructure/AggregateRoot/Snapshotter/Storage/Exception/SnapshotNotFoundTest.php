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

namespace Streak\Infrastructure\AggregateRoot\Snapshotter\Storage\Exception;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\AggregateRoot;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\AggregateRoot\Snapshotter\Storage\Exception\SnapshotNotFound
 */
class SnapshotNotFoundTest extends TestCase
{
    /**
     * @var AggregateRoot|MockObject
     */
    private $aggregate;

    /**
     * @var AggregateRoot\Id|MockObject
     */
    private $aggregateId;

    protected function setUp() : void
    {
        $this->aggregate = $this->getMockBuilder(AggregateRoot::class)->setMockClassName('streak__aggregate_1')->getMockForAbstractClass();
        $this->aggregateId = $this->getMockBuilder(AggregateRoot\Id::class)->setMockClassName('streak__aggregate_id_1')->getMockForAbstractClass();
    }

    public function testException()
    {
        $this->aggregate
            ->expects($this->atLeastOnce())
            ->method('aggregateRootId')
            ->willReturn($this->aggregateId)
        ;
        $this->aggregateId
            ->expects($this->atLeastOnce())
            ->method('toString')
            ->willReturn('uuid-1')
        ;

        $exception = new SnapshotNotFound($this->aggregate);

        $this->assertSame($this->aggregate, $exception->aggregate());
        $this->assertSame('Snapshot for aggregate "streak__aggregate_id_1#uuid-1" not found.', $exception->getMessage());
    }
}
