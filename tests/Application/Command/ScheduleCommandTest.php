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

namespace Streak\Application\Command;

use PHPUnit\Framework\TestCase;
use Streak\Application\Command;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Application\Command\ScheduleCommand
 */
class ScheduleCommandTest extends TestCase
{
    /**
     * @var Command|\PHPUnit_Framework_MockObject_MockObject
     */
    private $command;

    protected function setUp()
    {
        $this->command = $this->getMockBuilder(Command::class)->getMockForAbstractClass();
    }

    public function testCommand()
    {
        $now = new \DateTime();
        $scheduled = new ScheduleCommand($this->command, $now);

        $this->assertSame($this->command, $scheduled->command());
        $this->assertSame($now, $scheduled->when());
    }
}
