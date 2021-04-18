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

/**
 * @covers \Streak\Domain\Event\Exception\NotSupportedType
 */
class NotSupportedTypeTest extends TestCase
{
    /**
     * @dataProvider typesProvider
     *
     * @param mixed $value
     */
    public function testItCreates(string $expectedMessage, $value): void
    {
        $exception = new NotSupportedType($value);
        self::assertEquals($value, $exception->value());
        self::assertEquals($expectedMessage, $exception->getMessage());
    }

    public function typesProvider(): array
    {
        return [
            ['Type callable is not supported for conversion!', function (): void {
            }],
            ['Type integer is not supported for conversion!', 1],
            ['Type double is not supported for conversion!', 2.5],
            ['Type boolean is not supported for conversion!', true],
            ['Type resource is not supported for conversion!', tmpfile()],
        ];
    }
}
