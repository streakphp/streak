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
 * @covers \Streak\Domain\Exception\InvalidAggregateIdGiven
 */
class InvalidAggregateIdGivenTest extends TestCase
{
    /**
     * @var Domain\AggregateRoot\Id|\PHPUnit_Framework_MockObject_MockObject
     */
    private $aggregateId;

    public function setUp() : void
    {
        $this->aggregateId = $this->getMockBuilder(Domain\AggregateRoot\Id::class)->getMockForAbstractClass();
    }

    public function testException()
    {
        $exception = new InvalidAggregateIdGiven($this->aggregateId);

        $this->assertSame($this->aggregateId, $exception->aggregateId());
    }
}
