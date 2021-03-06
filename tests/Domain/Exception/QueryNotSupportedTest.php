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
use Streak\Domain\Query;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Exception\QueryNotSupported
 */
class QueryNotSupportedTest extends TestCase
{
    private $query;

    protected function setUp(): void
    {
        $this->query = $this->getMockBuilder(Query::class)->getMockForAbstractClass();
    }

    public function testException(): void
    {
        $exception = new QueryNotSupported($this->query);

        self::assertSame($this->query, $exception->query());
    }
}
