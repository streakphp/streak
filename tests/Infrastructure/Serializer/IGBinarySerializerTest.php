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

namespace Streak\Infrastructure\Serializer;

use PHPUnit\Framework\TestCase;
use Streak\Infrastructure\Domain\Serializer\IGBinarySerializer;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\Serializer\IGBinarySerializer
 */
class IGBinarySerializerTest extends TestCase
{
    /**
     * @dataProvider values
     *
     * @param mixed $value
     */
    public function testSerialize($value): void
    {
        $serializer = new IGBinarySerializer();

        $serialized = $serializer->serialize($value);
        $unserialized = $serializer->unserialize($serialized);

        self::assertEquals(igbinary_serialize($value), $serialized);
        self::assertEquals(igbinary_unserialize($serialized), $unserialized);
        self::assertEquals($value, $unserialized);
    }

    public function values(): array
    {
        return [
            ['string'],
            [0],
            [0.0],
            [true],
            [false],
            ['this', 'is' => 'an', 6 => 'array'],
            [new \stdClass()],
            [$this],
        ];
    }
}
