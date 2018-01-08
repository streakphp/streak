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
use Streak\Application\Exception;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Application\Command\ScheduleCommandHandler
 */
class ScheduleCommandHandlerTest extends TestCase
{
    /**
     * @var ScheduledCommand\Repository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $repository;

    /**
     * @var Command|\PHPUnit_Framework_MockObject_MockObject
     */
    private $command;

    protected function setUp()
    {
        $this->repository = $this->getMockBuilder(ScheduledCommand\Repository::class)->getMockForAbstractClass();
        $this->command = $this->getMockBuilder(Command::class)->getMockForAbstractClass();
    }

    public function testSuccess()
    {
        $command = new ScheduleCommand($this->command, new \DateTime());

        $this->repository
            ->expects($this->once())
            ->method('add')
            ->with($command);

        $handler = new ScheduleCommandHandler($this->repository);
        $handler->handle($command);
    }

    public function testFailure()
    {
        $handler = new ScheduleCommandHandler($this->repository);

        $exception = new Exception\CommandNotSupported($this->command);
        $this->expectExceptionObject($exception);

        $handler->handle($this->command);
    }
}
