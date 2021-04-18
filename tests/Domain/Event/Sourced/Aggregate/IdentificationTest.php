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

namespace Streak\Domain\Event\Sourced\Aggregate;

use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Sourced\Aggregate\Identification
 */
class IdentificationTest extends TestCase
{
    private Event\Sourced\Aggregate\Id $id;

    protected function setUp(): void
    {
        $this->id = $this->getMockBuilder(Event\Sourced\Aggregate\Id::class)->getMockForAbstractClass();
    }

    public function testObject(): void
    {
        $identification = $this->getMockBuilder(Identification::class)->setConstructorArgs([$this->id])->getMockForTrait();

        self::assertSame($this->id, $identification->producerId());
        self::assertSame($this->id, $identification->aggregateId());
        self::assertSame($this->id, $identification->entityId());
        self::assertSame($this->id, $identification->id());
    }
}
