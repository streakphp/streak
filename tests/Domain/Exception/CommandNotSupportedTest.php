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
 * @covers \Streak\Domain\Exception\CommandNotSupported
 */
class CommandNotSupportedTest extends TestCase
{
    private $command;

    protected function setUp(): void
    {
        $this->command = $this->getMockBuilder(Domain\Command::class)->getMockForAbstractClass();
    }

    public function testException(): void
    {
        $exception = new CommandNotSupported($this->command);

        self::assertSame($this->command, $exception->command());
    }
}
