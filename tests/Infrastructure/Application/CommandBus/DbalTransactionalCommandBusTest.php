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

namespace Streak\Infrastructure\Application\CommandBus;

use Doctrine\DBAL\Driver\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Application\CommandBus;
use Streak\Domain\Command;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Application\CommandBus\DbalTransactionalCommandBus
 */
class DbalTransactionalCommandBusTest extends TestCase
{
    private CommandBus|MockObject $bus;
    private Connection|MockObject $connection;
    private Command|MockObject $command;

    protected function setUp(): void
    {
        $this->bus = $this->createMock(CommandBus::class);
        $this->connection = $this->createMock(Connection::class);
        $this->command = $this->createMock(Command::class);
    }

    public function testBus(): void
    {
        $bus = new DbalTransactionalCommandBus($this->bus, $this->connection);

        $this->connection
            ->expects(self::once())
            ->method('beginTransaction')
            ->with()
        ;

        $this->bus
            ->expects(self::once())
            ->method('dispatch')
            ->with($this->command)
        ;

        $this->connection
            ->expects(self::once())
            ->method('commit')
            ->with()
        ;

        $this->connection
            ->expects(self::never())
            ->method('rollBack')
            ->with()
        ;

        $bus->dispatch($this->command);
    }

    public function testException(): void
    {
        $bus = new DbalTransactionalCommandBus($this->bus, $this->connection);

        $this->connection
            ->expects(self::once())
            ->method('beginTransaction')
            ->with()
        ;

        $this->bus
            ->expects(self::once())
            ->method('dispatch')
            ->with($this->command)
            ->willThrowException($exception = new \RuntimeException('test'))
        ;

        $this->connection
            ->expects(self::never())
            ->method('commit')
        ;

        $this->connection
            ->expects(self::once())
            ->method('rollBack')
            ->with()
        ;

        $this->expectExceptionObject($exception);

        $bus->dispatch($this->command);
    }
}
