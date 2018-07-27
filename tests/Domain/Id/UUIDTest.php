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

namespace Streak\Domain\Id;

use PHPUnit\Framework\TestCase;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Id\UUID
 */
class UUIDTest extends TestCase
{
    public function testObject()
    {
        $uuid1 = UUID::create();

        $this->assertInstanceOf(UUID::class, $uuid1);
        $this->assertTrue($uuid1->equals($uuid1));
        $this->assertFalse($uuid1->equals(new \stdClass()));

        $uuid2a = new UUID('0bc68acb-abd1-48ca-b8e2-5638efa5891b');
        $uuid2b = new UUID('0BC68ACB-ABD1-48CA-B8E2-5638EFA5891B');

        $this->assertSame('0BC68ACB-ABD1-48CA-B8E2-5638EFA5891B', $uuid2a->toString());
        $this->assertSame('0BC68ACB-ABD1-48CA-B8E2-5638EFA5891B', $uuid2b->toString());

        $this->assertFalse($uuid1->equals($uuid2a));
        $this->assertFalse($uuid2a->equals($uuid1));
        $this->assertFalse($uuid1->equals($uuid2b));
        $this->assertFalse($uuid2b->equals($uuid1));
        $this->assertTrue($uuid2a->equals($uuid2b));
        $this->assertTrue($uuid2b->equals($uuid2a));

        $uuid3a = UUID::fromString('0bc68acb-abd1-48ca-b8e2-5638efa5891b');
        $uuid3b = UUID::fromString('0BC68ACB-ABD1-48CA-B8E2-5638EFA5891B');

        $this->assertFalse($uuid1->equals($uuid3a));
        $this->assertFalse($uuid3a->equals($uuid1));
        $this->assertFalse($uuid1->equals($uuid3b));
        $this->assertFalse($uuid3b->equals($uuid1));
        $this->assertTrue($uuid3a->equals($uuid3b));
        $this->assertTrue($uuid3b->equals($uuid3a));
    }

    public function testInvalidUUID()
    {
        $this->expectExceptionObject(new \InvalidArgumentException());

        new UUID('invalid uuid');
    }

    public function testNoNullUUID()
    {
        $this->expectExceptionObject(new \InvalidArgumentException());

        new UUID('00000000-0000-0000-0000-000000000000');
    }

    public function testExtendedUUIDCreatesProperClassFromString()
    {
        $uuid = ExtendedUUID::fromString('0bc68acb-abd1-48ca-b8e2-5638efa5891b');

        $this->assertInstanceOf(ExtendedUUID::class, $uuid);
    }
}

class ExtendedUUID extends UUID
{
}
