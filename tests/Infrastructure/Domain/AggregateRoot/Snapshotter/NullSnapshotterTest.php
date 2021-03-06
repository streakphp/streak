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

namespace Streak\Infrastructure\Domain\AggregateRoot\Snapshotter;

use PHPUnit\Framework\TestCase;
use Streak\Domain\AggregateRoot;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\AggregateRoot\Snapshotter\NullSnapshotter
 */
class NullSnapshotterTest extends TestCase
{
    private AggregateRoot $aggregate1;

    protected function setUp(): void
    {
        $this->aggregate1 = $this->getMockBuilder(AggregateRoot::class)->getMockForAbstractClass();
    }

    public function testObject(): void
    {
        $snapshotter = new NullSnapshotter();

        self::assertNull($snapshotter->restoreToSnapshot($this->aggregate1));
        $snapshotter->takeSnapshot($this->aggregate1);
        self::assertNull($snapshotter->restoreToSnapshot($this->aggregate1));
    }
}
