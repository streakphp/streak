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

namespace Streak\Domain\Query;

use PHPUnit\Framework\TestCase;
use Streak\Domain\Exception\QueryNotSupported;
use Streak\Domain\Query;
use Streak\Domain\Query\HandlingTest\HandlingStub;
use Streak\Domain\Query\HandlingTest\NotSupportedQuery1;
use Streak\Domain\Query\HandlingTest\SupportedQuery1;
use Streak\Domain\Query\HandlingTest\SupportedQuery2;
use Streak\Domain\Query\HandlingTest\SupportedQuery3;
use Streak\Domain\Query\HandlingTest\SupportedQuery4;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Query\Handling
 */
class HandlingTest extends TestCase
{
    public function testSuccess(): void
    {
        $handler = new HandlingStub();

        self::assertSame('query1', $handler->handleQuery(new SupportedQuery1()));
        self::assertSame(1, $handler->handleQuery(new SupportedQuery2()));
        self::assertSame(['query1', 1], $handler->handleQuery(new SupportedQuery3()));
        self::assertEquals(new \stdClass(), $handler->handleQuery(new SupportedQuery4()));
    }

    /**
     * @dataProvider failingQueries
     */
    public function testFailure(Query $query): void
    {
        $this->expectExceptionObject(new QueryNotSupported($query));

        $handler = new HandlingStub();
        $handler->handleQuery($query);
    }

    public function failingQueries(): array
    {
        return [
            [new NotSupportedQuery1()],
        ];
    }
}

namespace Streak\Domain\Query\HandlingTest;

use Streak\Domain\Query;

class HandlingStub
{
    use Query\Handling;

    public function handleQuery1(SupportedQuery1 $query)
    {
        return 'query1';
    }

    public function handleQuery2(SupportedQuery2 $query)
    {
        return 1;
    }

    public function handleQuery3(SupportedQuery3 $query)
    {
        return ['query1', 1];
    }

    public function handleQuery4(SupportedQuery4 $query)
    {
        return new \stdClass();
    }

    public function notStartingWithHandle(NotSupportedQuery1 $query)
    {
        return 'notsupported';
    }

    public function handleHandlingMethodWithMoreThanOneParameter1(SupportedQuery1 $query1, SupportedQuery2 $query2)
    {
        return 'notsupported';
    }

    public function handleHandlingMethodWithMoreThanOneParameter2(SupportedQuery1 $query1, SupportedQuery2 $query2, NotSupportedQuery1 $query3)
    {
        return 'notsupported';
    }

    public function handleNotRequiredQueryParameter(?SupportedQuery1 $query1)
    {
        return 'notsupported';
    }

    public function handleNonQueryParameter(\stdClass $query1)
    {
        return 'notsupported';
    }

    private function handlePrivateMethodHandlingMethod(NotSupportedQuery1 $query)
    {
        return 'notsupported';
    }
}

class SupportedQuery1 implements Query
{
}

class SupportedQuery2 implements Query
{
}

class SupportedQuery3 implements Query
{
}

class SupportedQuery4 implements Query
{
}

class NotSupportedQuery1 implements Query
{
}
