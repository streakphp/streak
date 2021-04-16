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
use Streak\Domain\Id\UUIDTest\ExtendedUUID1;
use Streak\Domain\Id\UUIDTest\ExtendedUUID2;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Id\UUID
 */
class UUIDTest extends TestCase
{
    public function testObject(): void
    {
        $uuid1 = UUID::random();

        self::assertInstanceOf(UUID::class, $uuid1);
        self::assertTrue($uuid1->equals($uuid1));
        self::assertFalse($uuid1->equals(new \stdClass()));

        $uuid2a = new UUID('0bc68acb-abd1-48ca-b8e2-5638efa5891b');
        $uuid2b = new UUID('0bc68acb-abd1-48ca-b8e2-5638efa5891b');

        self::assertSame('0bc68acb-abd1-48ca-b8e2-5638efa5891b', $uuid2a->toString());
        self::assertSame('0bc68acb-abd1-48ca-b8e2-5638efa5891b', $uuid2b->toString());

        self::assertFalse($uuid1->equals($uuid2a));
        self::assertFalse($uuid2a->equals($uuid1));
        self::assertFalse($uuid1->equals($uuid2b));
        self::assertFalse($uuid2b->equals($uuid1));
        self::assertTrue($uuid2a->equals($uuid2b));
        self::assertTrue($uuid2b->equals($uuid2a));

        $uuid3a = UUID::fromString('0bc68acb-abd1-48ca-b8e2-5638efa5891b');
        $uuid3b = UUID::fromString('0bc68acb-abd1-48ca-b8e2-5638efa5891b');

        self::assertFalse($uuid1->equals($uuid3a));
        self::assertFalse($uuid3a->equals($uuid1));
        self::assertFalse($uuid1->equals($uuid3b));
        self::assertFalse($uuid3b->equals($uuid1));
        self::assertTrue($uuid3a->equals($uuid3b));
        self::assertTrue($uuid3b->equals($uuid3a));
    }

    public function testInvalidUUID(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Given value "invalid uuid" is not an uuid.'));

        new UUID('invalid uuid');
    }

    public function testNoNullUUID(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException());

        new UUID('00000000-0000-0000-0000-000000000000');
    }

    public function testExtendedUUIDCreatesProperClassFromString(): void
    {
        $uuid = ExtendedUUID1::fromString('0bc68acb-abd1-48ca-b8e2-5638efa5891b');

        self::assertInstanceOf(ExtendedUUID1::class, $uuid);
    }

    public function testExtendedUUIDsComparison(): void
    {
        $uuid = UUID::fromString('0bc68acb-abd1-48ca-b8e2-5638efa5891b');
        $uuid1 = ExtendedUUID1::fromString('0bc68acb-abd1-48ca-b8e2-5638efa5891b');
        $uuid2 = ExtendedUUID2::fromString('0bc68acb-abd1-48ca-b8e2-5638efa5891b');

        self::assertTrue($uuid->equals($uuid));
        self::assertTrue($uuid->equals($uuid1));
        self::assertTrue($uuid->equals($uuid2));

        self::assertFalse($uuid1->equals($uuid));
        self::assertTrue($uuid1->equals($uuid1));
        self::assertFalse($uuid1->equals($uuid2));

        self::assertFalse($uuid2->equals($uuid));
        self::assertFalse($uuid2->equals($uuid1));
        self::assertTrue($uuid2->equals($uuid2));
    }
}

namespace Streak\Domain\Id\UUIDTest;

use Streak\Domain\Id\UUID;

class ExtendedUUID1 extends UUID
{
}

class ExtendedUUID2 extends UUID
{
}
