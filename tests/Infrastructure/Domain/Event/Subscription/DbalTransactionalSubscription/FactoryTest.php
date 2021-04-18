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

namespace Streak\Infrastructure\Domain\Event\Subscription\DbalTransactionalSubscription;

use Doctrine\DBAL\Driver\Connection;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Subscription;
use Streak\Infrastructure\Domain\Event\Subscription\DbalTransactionalSubscription;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\Event\Subscription\DbalTransactionalSubscription\Factory
 */
class FactoryTest extends TestCase
{
    private Subscription\Factory $factory;

    private Connection $connection;

    private Listener $listener;

    private Subscription $subscription;

    protected function setUp(): void
    {
        $this->factory = $this->getMockBuilder(Subscription\Factory::class)->getMockForAbstractClass();
        $this->connection = $this->getMockBuilder(Connection::class)->getMockForAbstractClass();
        $this->listener = $this->getMockBuilder(Listener::class)->getMockForAbstractClass();
        $this->subscription = $this->getMockBuilder(Subscription::class)->getMockForAbstractClass();
    }

    public function testListener(): void
    {
        $this->factory
            ->expects(self::exactly(2))
            ->method('create')
            ->with($this->listener)
            ->willReturn($this->subscription)
        ;

        $this->listener
            ->expects(self::never())
            ->method(self::anything())
        ;

        $factory = new Factory($this->factory, $this->connection, 1);
        $subscription = $factory->create($this->listener);
        self::assertEquals(new DbalTransactionalSubscription($this->subscription, $this->connection, 1), $subscription); // decorated

        $factory = new Factory($this->factory, $this->connection, \PHP_INT_MAX);
        $subscription = $factory->create($this->listener);
        self::assertEquals(new DbalTransactionalSubscription($this->subscription, $this->connection, \PHP_INT_MAX), $subscription); // decorated
    }

    public function testWrongMaximumTransactionSize(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Maximum transaction size must be at least "1", but "0" given.'));

        new Factory($this->factory, $this->connection, 0);
    }
}
