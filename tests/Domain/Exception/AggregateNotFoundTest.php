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

namespace Streak\Domain\Exception;

use PHPUnit\Framework\TestCase;
use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Exception\AggregateNotFound
 */
class AggregateNotFoundTest extends TestCase
{
    /**
     * @var Domain\AggregateRoot\Id|\PHPUnit_Framework_MockObject_MockObject
     */
    private $aggregateId;

    public function setUp() : void
    {
        $this->aggregateId = $this->getMockBuilder(Domain\AggregateRoot\Id::class)->setMockClassName('aggregate_id_1')->getMockForAbstractClass();
    }

    public function testException()
    {
        $this->aggregateId
            ->expects($this->once())
            ->method('toString')
            ->willReturn('8db25a31-45ce-499f-95f7-8b8d4fffc366')
        ;

        $exception = new AggregateNotFound($this->aggregateId);

        $this->assertSame('Aggregate "aggregate_id_1@8db25a31-45ce-499f-95f7-8b8d4fffc366" not found.', $exception->getMessage());
        $this->assertSame($this->aggregateId, $exception->aggregateId());
    }
}
