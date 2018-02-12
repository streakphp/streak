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

namespace Streak\Domain\Event;

use PHPUnit\Framework\TestCase;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Metadata
 */
class MetadataTest extends TestCase
{
    public function testMetadata()
    {
        $object = new \stdClass();

        $metadata1 = Metadata::fromObject($object);

        $this->assertTrue($metadata1->empty());
        $this->assertSame([], $metadata1->toArray());

        $object = new \stdClass();
        $object->__streak_metadata = 'not-an-array';

        $metadata1 = Metadata::fromObject($object);

        $this->assertTrue($metadata1->empty());
        $this->assertSame([], $metadata1->toArray());

        $metadata1->set('attribute1', 'value1');

        $this->assertTrue($metadata1->has('attribute1'));
        $this->assertSame('value1', $metadata1->get('attribute1'));
        $this->assertSame(['attribute1' => 'value1'], $metadata1->toArray());

        $this->assertFalse($metadata1->has('attribute2'));
        $this->assertNull($metadata1->get('attribute2'));
        $this->assertSame('default2', $metadata1->get('attribute2', 'default2'));

        $metadata1->toObject($object);

        $metadata2 = Metadata::fromObject($object);

        $this->assertNotSame($metadata1, $metadata2);
        $this->assertEquals($metadata1, $metadata2);

        $metadata3 = Metadata::fromArray(['attribute1' => 'value1']);

        $this->assertNotSame($metadata1, $metadata3);
        $this->assertNotSame($metadata2, $metadata3);
        $this->assertEquals($metadata1, $metadata3);
        $this->assertEquals($metadata2, $metadata3);

        Metadata::clear($object);

        $metadata4 = Metadata::fromObject($object);

        $this->assertTrue($metadata4->empty());
    }
}
