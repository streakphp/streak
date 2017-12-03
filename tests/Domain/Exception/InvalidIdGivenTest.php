<?php

/*
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
 * @covers \Streak\Domain\Exception\InvalidIdGiven
 */
class InvalidIdGivenTest extends TestCase
{
    /**
     * @var Domain\AggregateRoot\Id|\PHPUnit_Framework_MockObject_MockObject
     */
    private $id;

    public function setUp()
    {
        $this->id = $this->getMockBuilder(Domain\Id::class)->getMockForAbstractClass();
    }
    public function testException()
    {
        $exception = new InvalidIdGiven($this->id);

        $this->assertSame($this->id, $exception->id());
    }
}
