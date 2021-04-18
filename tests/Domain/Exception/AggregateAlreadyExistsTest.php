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
 * @covers \Streak\Domain\Exception\AggregateAlreadyExists
 */
class AggregateAlreadyExistsTest extends TestCase
{
    /**
     * @var Domain\AggregateRoot|\PHPUnit_Framework_MockObject_MockObject
     */
    private $aggregate;

    protected function setUp(): void
    {
        $this->aggregate = $this->getMockBuilder(Domain\AggregateRoot::class)->getMockForAbstractClass();
    }

    public function testException(): void
    {
        $exception = new AggregateAlreadyExists($this->aggregate);

        self::assertSame($this->aggregate, $exception->aggregate());
    }
}
