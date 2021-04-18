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

namespace Streak\Application\Exception;

use PHPUnit\Framework\TestCase;
use Streak\Application;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Application\Exception\CommandNotSupported
 */
class CommandNotSupportedTest extends TestCase
{
    private $command;
    private ?\Exception $previous = null;

    protected function setUp(): void
    {
        $this->command = $this->getMockBuilder(Application\Command::class)->getMockForAbstractClass();
        $this->previous = new \Exception();
    }

    public function testException(): void
    {
        $exception = new CommandNotSupported($this->command, $this->previous);

        self::assertSame($this->command, $exception->command());
        self::assertSame($this->previous, $exception->getPrevious());
    }
}
