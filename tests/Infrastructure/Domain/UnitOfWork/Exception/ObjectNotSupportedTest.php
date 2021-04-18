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

namespace Streak\Infrastructure\Domain\UnitOfWork\Exception;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Streak\Infrastructure\Domain\UnitOfWork\Exception\ObjectNotSupported
 */
class ObjectNotSupportedTest extends TestCase
{
    public function testIt(): void
    {
        $object = new \stdClass();
        $previous = new \Exception();
        $exception = new ObjectNotSupported($object, $previous);
        self::assertSame($object, $exception->object());
        self::assertSame($previous, $exception->getPrevious());
        self::assertEquals('Object is not supported.', $exception->getMessage());
    }
}
