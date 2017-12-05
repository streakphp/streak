<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
    private $previous;

    protected function setUp()
    {
        $this->command = $this->getMockBuilder(Application\Command::class)->getMockForAbstractClass();
        $this->previous = new \Exception;
    }

    public function testException()
    {
        $exception = new CommandNotSupported($this->command, $this->previous);

        $this->assertSame($this->command, $exception->command());
        $this->assertSame($this->previous, $exception->getPrevious());
    }
}
