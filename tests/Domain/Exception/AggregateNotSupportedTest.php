<?php

declare(strict_types=1);

/**
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain\Exception;

use PHPUnit\Framework\TestCase;
use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Exception\AggregateNotSupported
 */
class AggregateNotSupportedTest extends TestCase
{
    /**
     * @var Domain\AggregateRoot|\PHPUnit_Framework_MockObject_MockObject
     */
    private $aggregate;

    public function setUp()
    {
        $this->aggregate = $this->getMockBuilder(Domain\AggregateRoot::class)->getMockForAbstractClass();
    }

    public function testException()
    {
        $exception = new AggregateNotSupported($this->aggregate);

        $this->assertSame($this->aggregate, $exception->aggregate());
    }
}
