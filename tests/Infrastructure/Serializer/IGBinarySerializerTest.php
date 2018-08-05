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

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Serializer\IGBinarySerializer
 */
class IGBinarySerializerTest extends TestCase
{
    /**
     * @dataProvider values
     */
    public function testSerialize($value)
    {
        $serializer = new IGBinarySerializer();

        $serialized = $serializer->serialize($value);
        $unserialized = $serializer->unserialize($serialized);

        $this->assertEquals(\igbinary_serialize($value), $serialized);
        $this->assertEquals(\igbinary_unserialize($serialized), $unserialized);
        $this->assertEquals($value, $unserialized);
    }

    public function values() : array
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
