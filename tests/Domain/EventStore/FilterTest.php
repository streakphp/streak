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

namespace Streak\Domain\EventStore;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Id;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\EventStore\Filter
 */
class FilterTest extends TestCase
{
    private Id|MockObject $id1;
    private Id|MockObject $id2;

    protected function setUp(): void
    {
        $this->id1 = $this->getMockBuilder(Id::class)->setMockClassName('id1')->getMock();
        $this->id2 = $this->getMockBuilder(Id::class)->setMockClassName('id2')->getMockForAbstractClass();
    }

    public function testFilter(): void
    {
        $this->id1
            ->expects(self::atLeast(1))
            ->method('equals')
            ->withConsecutive(
                [$this->id2],
                [$this->id1],
                [$this->id2]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true,
                false
            )
        ;
        $this->id2
            ->expects(self::atLeast(1))
            ->method('equals')
            ->withConsecutive(
                [$this->id2],
                [$this->id2]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                true
            )
        ;

        $filter = Filter::nothing();

        self::assertEquals(new Filter(), $filter);
        self::assertEmpty($filter->producerIds());
        self::assertEmpty($filter->producerTypes());

        $filter = $filter->filterProducerIds($this->id1);

        self::assertEquals([$this->id1], $filter->producerIds());
        self::assertEmpty($filter->producerTypes());

        $filter = $filter->filterProducerTypes('type-1');

        self::assertEquals([$this->id1], $filter->producerIds());
        self::assertEquals(['type-1'], $filter->producerTypes());

        $filter = $filter->filterProducerIds($this->id2);

        self::assertEquals([$this->id1, $this->id2], $filter->producerIds());
        self::assertEquals(['type-1'], $filter->producerTypes());

        $filter = $filter->filterProducerTypes('type-2');

        self::assertEquals([$this->id1, $this->id2], $filter->producerIds());
        self::assertEquals(['type-1', 'type-2'], $filter->producerTypes());

        $filter = $filter->filterProducerIds($this->id1);
        $filter = $filter->filterProducerIds($this->id2);

        self::assertEquals([$this->id1, $this->id2], $filter->producerIds());
        self::assertEquals(['type-1', 'type-2'], $filter->producerTypes());

        $filter = $filter->filterProducerTypes('type-1');
        $filter = $filter->filterProducerTypes('type-2');

        self::assertEquals([$this->id1, $this->id2], $filter->producerIds());
        self::assertEquals(['type-1', 'type-2'], $filter->producerTypes());

        $filter = $filter->doNotFilterProducerIds();

        self::assertEmpty($filter->producerIds());
        self::assertEquals(['type-1', 'type-2'], $filter->producerTypes());

        $filter = $filter->doNotFilterProducerTypes();

        self::assertEquals(new Filter(), $filter);
        self::assertEmpty($filter->producerIds());
        self::assertEmpty($filter->producerTypes());
    }
}
