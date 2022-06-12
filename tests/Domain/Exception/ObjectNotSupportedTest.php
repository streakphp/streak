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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Exception\ObjectNotSupported
 */
class ObjectNotSupportedTest extends TestCase
{
    private Domain\AggregateRoot|MockObject $object;

    protected function setUp(): void
    {
        $this->object = $this->getMockBuilder(Domain\AggregateRoot::class)->getMockForAbstractClass();
    }

    public function testException(): void
    {
        $exception = new ObjectNotSupported($this->object);

        self::assertSame($this->object, $exception->object());
    }
}
