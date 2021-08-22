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

namespace Streak\Domain\Event\Exception;

use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Exception\AggregateAlreadyRegistered
 */
class AggregateAlreadyRegisteredTest extends TestCase
{
    private Event\Sourced\Aggregate $aggregate;

    protected function setUp(): void
    {
        $this->aggregate = $this->getMockBuilder(Event\Sourced\Aggregate::class)->getMockForAbstractClass();
    }

    public function testException(): void
    {
        $exception = new AggregateAlreadyRegistered($this->aggregate);

        self::assertSame('Aggregate already registered.', $exception->getMessage());
        self::assertSame($this->aggregate, $exception->aggregate());
    }
}
