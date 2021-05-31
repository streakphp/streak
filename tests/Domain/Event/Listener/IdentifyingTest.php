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

namespace Streak\Domain\Event\Listener;

use PHPUnit\Framework\TestCase;
use Streak\Application\Event\Listener;
use Streak\Application\Event\Listener\Identifying;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Application\Event\Listener\Identifying
 */
class IdentifyingTest extends TestCase
{
    private Listener\Id $id;

    protected function setUp(): void
    {
        $this->id = $this->getMockBuilder(Listener\Id::class)->getMockForAbstractClass();
    }

    public function testObject(): void
    {
        $identification = $this->getMockBuilder(Identifying::class)->setConstructorArgs([$this->id])->getMockForTrait();

        self::assertSame($this->id, $identification->id());
    }
}
