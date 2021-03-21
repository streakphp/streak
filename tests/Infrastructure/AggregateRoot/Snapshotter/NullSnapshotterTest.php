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

namespace Streak\Infrastructure\AggregateRoot\Snapshotter;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\AggregateRoot;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\AggregateRoot\Snapshotter\NullSnapshotter
 */
class NullSnapshotterTest extends TestCase
{
    /**
     * @var AggregateRoot|MockObject
     */
    private $aggregate1;

    protected function setUp() : void
    {
        $this->aggregate1 = $this->getMockBuilder(AggregateRoot::class)->getMockForAbstractClass();
    }

    public function testObject()
    {
        $snapshotter = new NullSnapshotter();

        $this->assertNull($snapshotter->restoreToSnapshot($this->aggregate1));
        $snapshotter->takeSnapshot($this->aggregate1);
        $this->assertNull($snapshotter->restoreToSnapshot($this->aggregate1));
    }
}
