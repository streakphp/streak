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
use Streak\Domain\AggregateRoot;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Exception\InvalidAggregateGiven
 */
class InvalidAggregateGivenTest extends TestCase
{
    private AggregateRoot $aggregate;

    protected function setUp(): void
    {
        $this->aggregate = $this->getMockBuilder(AggregateRoot::class)->getMockForAbstractClass();
    }

    public function testException(): void
    {
        $exception = new InvalidAggregateGiven($this->aggregate);

        self::assertSame($this->aggregate, $exception->aggregate());
    }
}
