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

namespace Streak\Domain\Exception;

use PHPUnit\Framework\TestCase;
use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Exception\ConcurrentWriteDetected
 */
class ConcurrentWriteDetectedTest extends TestCase
{
    private Domain\Id $id;

    protected function setUp(): void
    {
        $this->id = $this->getMockBuilder(Domain\Id::class)->getMockForAbstractClass();
    }

    public function testException(): void
    {
        $exception = new ConcurrentWriteDetected($this->id);

        self::assertSame($this->id, $exception->id());
    }
}
