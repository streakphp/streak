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

namespace Streak\Infrastructure\Domain\AggregateRoot\Snapshotter\Storage\Exception;

use PHPUnit\Framework\TestCase;
use Streak\Domain\AggregateRoot;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\AggregateRoot\Snapshotter\Storage\Exception\SnapshotNotFound
 */
class SnapshotNotFoundTest extends TestCase
{
    private AggregateRoot $aggregate;

    private AggregateRoot\Id $aggregateId;

    protected function setUp(): void
    {
        $this->aggregate = $this->getMockBuilder(AggregateRoot::class)->setMockClassName('streak__aggregate_1')->getMockForAbstractClass();
        $this->aggregateId = $this->getMockBuilder(AggregateRoot\Id::class)->setMockClassName('streak__aggregate_id_1')->getMockForAbstractClass();
    }

    public function testException(): void
    {
        $this->aggregate
            ->expects(self::atLeastOnce())
            ->method('aggregateRootId')
            ->willReturn($this->aggregateId)
        ;
        $this->aggregateId
            ->expects(self::atLeastOnce())
            ->method('toString')
            ->willReturn('uuid-1')
        ;

        $exception = new SnapshotNotFound($this->aggregate);

        self::assertSame($this->aggregate, $exception->aggregate());
        self::assertSame('Snapshot for aggregate "streak__aggregate_id_1#uuid-1" not found.', $exception->getMessage());
    }
}
