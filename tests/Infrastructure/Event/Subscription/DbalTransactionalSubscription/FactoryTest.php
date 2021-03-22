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

namespace Streak\Infrastructure\Event\Subscription\DbalTransactionalSubscription;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Subscription;
use Streak\Infrastructure\Event\Subscription\DbalTransactionalSubscription;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Event\Subscription\DbalTransactionalSubscription\Factory
 */
class FactoryTest extends TestCase
{
    /**
     * @var Subscription\Factory|MockObject
     */
    private $factory;

    /**
     * @var Connection|MockObject
     */
    private $connection;

    /**
     * @var Listener|MockObject
     */
    private $listener;

    /**
     * @var Subscription|MockObject
     */
    private $subscription;

    protected function setUp() : void
    {
        $this->factory = $this->getMockBuilder(Subscription\Factory::class)->getMockForAbstractClass();
        $this->connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMockForAbstractClass();
        $this->listener = $this->getMockBuilder(Listener::class)->getMockForAbstractClass();
        $this->subscription = $this->getMockBuilder(Subscription::class)->getMockForAbstractClass();
    }

    public function testListener()
    {
        $this->factory
            ->expects($this->exactly(2))
            ->method('create')
            ->with($this->listener)
            ->willReturn($this->subscription)
        ;

        $this->listener
            ->expects($this->never())
            ->method($this->anything())
        ;

        $factory = new Factory($this->factory, $this->connection, 1);
        $subscription = $factory->create($this->listener);
        $this->assertEquals(new DbalTransactionalSubscription($this->subscription, $this->connection, 1), $subscription); // decorated

        $factory = new Factory($this->factory, $this->connection, PHP_INT_MAX);
        $subscription = $factory->create($this->listener);
        $this->assertEquals(new DbalTransactionalSubscription($this->subscription, $this->connection, PHP_INT_MAX), $subscription); // decorated
    }

    public function testWrongMaximumTransactionSize()
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Maximum transaction size must be at least "1", but "0" given.'));

        new Factory($this->factory, $this->connection, 0);
    }
}
