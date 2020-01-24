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

namespace Streak\Infrastructure\Event\Subscription;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Subscription;
use Streak\Domain\EventStore;
use Streak\Infrastructure\Event\Converter\NestedObjectConverter;
use Streak\Infrastructure\Event\Subscription\DbalTransactionalSubscriptionTest\Event1;
use Streak\Infrastructure\Event\Subscription\DbalTransactionalSubscriptionTest\Event2;
use Streak\Infrastructure\Event\Subscription\DbalTransactionalSubscriptionTest\Event3;
use Streak\Infrastructure\Event\Subscription\DbalTransactionalSubscriptionTest\ProducerId1;
use Streak\Infrastructure\EventStore\DbalPostgresEventStore;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Event\Subscription\DbalTransactionalSubscription
 */
class DbalTransactionalSubscriptionTest extends TestCase
{
    /**
     * @var Subscription|MockObject
     */
    private $subscription;

    /**
     * @var Listener|MockObject
     */
    private $listener;

    /**
     * @var EventStore
     */
    private $store1;

    /**
     * @var EventStore
     */
    private $store2;

    /**
     * @var Listener\Id|MockObject
     */
    private $subscriptionId;

    private $producerId1;

    private $event1;

    private $event2;

    private $event3;

    /**
     * @var Connection|MockObject
     */
    private $connection;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private static $connection1;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private static $connection2;

    public static function setUpBeforeClass()
    {
        self::$connection1 = DriverManager::getConnection(
            [
                'driver' => 'pdo_pgsql',
                'host' => $_ENV['PHPUNIT_POSTGRES_HOSTNAME'],
                'port' => (int) $_ENV['PHPUNIT_POSTGRES_PORT'],
                'dbname' => $_ENV['PHPUNIT_POSTGRES_DATABASE'],
                'user' => $_ENV['PHPUNIT_POSTGRES_USERNAME'],
                'password' => $_ENV['PHPUNIT_POSTGRES_PASSWORD'],
            ]
        );
        self::$connection2 = DriverManager::getConnection(
            [
                'driver' => 'pdo_pgsql',
                'host' => $_ENV['PHPUNIT_POSTGRES_HOSTNAME'],
                'port' => (int) $_ENV['PHPUNIT_POSTGRES_PORT'],
                'dbname' => $_ENV['PHPUNIT_POSTGRES_DATABASE'],
                'user' => $_ENV['PHPUNIT_POSTGRES_USERNAME'],
                'password' => $_ENV['PHPUNIT_POSTGRES_PASSWORD'],
            ]
        );
    }

    protected function setUp()
    {
        $this->subscription = $this->getMockBuilder(Subscription::class)->getMockForAbstractClass();
        $this->listener = $this->getMockBuilder(Listener::class)->getMockForAbstractClass();
        $this->subscriptionId = $this->getMockBuilder(Listener\Id::class)->getMockForAbstractClass();
        $this->connection = $this->getMockBuilder(Connection::class)->getMockForAbstractClass();
        $this->producerId1 = ProducerId1::random();
        $this->event1 = new Event1();
        $this->event1 = Event\Envelope::new($this->event1, $this->producerId1);
        $this->event2 = new Event2();
        $this->event2 = Event\Envelope::new($this->event2, $this->producerId1);
        $this->event3 = new Event3();
        $this->event3 = Event\Envelope::new($this->event3, $this->producerId1);

        self::$connection1->close();
        self::$connection1->connect();
        self::$connection2->close();
        self::$connection2->connect();

        $this->store1 = new DbalPostgresEventStore(self::$connection1, new NestedObjectConverter());
        $this->store1->drop();
        $this->store1->create();

        $this->store2 = new DbalPostgresEventStore(self::$connection2, new NestedObjectConverter());
    }

    public function testObject()
    {
        $subscription = new DbalTransactionalSubscription($this->subscription, $this->connection, 1);

        $this->assertSame($this->subscription, $subscription->subscription());

        $this->subscription
            ->expects($this->once())
            ->method('listener')
            ->with()
            ->willReturn($this->listener)
        ;

        $this->assertSame($this->listener, $subscription->listener());

        $this->subscription
            ->expects($this->exactly(2))
            ->method('version')
            ->with()
            ->willReturnOnConsecutiveCalls(1000, 1001)
        ;

        $this->assertSame(1000, $subscription->version());
        $this->assertSame(1001, $subscription->version());

        $this->subscription
            ->expects($this->once())
            ->method('subscriptionId')
            ->with()
            ->willReturn($this->subscriptionId)
        ;

        $this->assertSame($this->subscriptionId, $subscription->subscriptionId());

        $this->subscription
            ->expects($this->once())
            ->method('startFor')
            ->with($this->event1)
        ;

        $subscription->startFor($this->event1);

        $this->subscription
            ->expects($this->once())
            ->method('restart')
            ->with()
        ;

        $subscription->restart();

        $this->subscription
            ->expects($this->exactly(2))
            ->method('starting')
            ->with()
            ->willReturnOnConsecutiveCalls(true, false)
        ;

        $this->assertTrue($subscription->starting());
        $this->assertFalse($subscription->starting());

        $this->subscription
            ->expects($this->exactly(2))
            ->method('started')
            ->with()
            ->willReturnOnConsecutiveCalls(true, false)
        ;

        $this->assertTrue($subscription->started());
        $this->assertFalse($subscription->started());

        $this->subscription
            ->expects($this->exactly(2))
            ->method('completed')
            ->with()
            ->willReturnOnConsecutiveCalls(false, true)
        ;

        $this->assertFalse($subscription->completed());
        $this->assertTrue($subscription->completed());
    }

    public function testSubscriberForSingleEventTransaction()
    {
        $subscription = new DbalTransactionalSubscription($this->subscription, self::$connection1, 1);

        $this->subscription
            ->expects($this->once())
            ->method('subscribeTo')
            ->with($this->store1, null)
            ->willReturnCallback(function (EventStore $store) {
                $this->assertTrue($store->stream()->empty());
                $this->assertTrue($this->store2->stream()->empty());

                $store->add($this->event1);

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                yield $this->event1;

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1], iterator_to_array($store->stream()));
                $this->assertFalse($this->store2->stream()->empty());
                $this->assertEquals([$this->event1], iterator_to_array($this->store2->stream()));

                $store->add($this->event2);

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                $this->assertFalse($this->store2->stream()->empty());
                $this->assertEquals([$this->event1], iterator_to_array($this->store2->stream()));

                yield $this->event2;

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                $this->assertFalse($this->store2->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2], iterator_to_array($this->store2->stream()));

                $store->add($this->event3);

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                $this->assertFalse($this->store2->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2], iterator_to_array($this->store2->stream()));

                yield $this->event3;

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                $this->assertFalse($this->store2->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store2->stream()));
            })
        ;

        $this->assertTrue($this->store1->stream()->empty());
        $this->assertTrue($this->store2->stream()->empty());

        $events = $subscription->subscribeTo($this->store1);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1, $this->event2, $this->event3], $events);
        $this->assertFalse($this->store1->stream()->empty());
        $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store1->stream()));
        $this->assertFalse($this->store2->stream()->empty());
        $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store2->stream()));
    }

    public function testSubscriberForMultipleEventsTransactionLimit1()
    {
        $subscription = new DbalTransactionalSubscription($this->subscription, self::$connection1, 2);

        $this->subscription
            ->expects($this->once())
            ->method('subscribeTo')
            ->with($this->store1, null)
            ->willReturnCallback(function (EventStore $store) {
                $this->assertTrue($store->stream()->empty());
                $this->assertTrue($this->store2->stream()->empty());

                $store->add($this->event1);

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                yield $this->event1;

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                $store->add($this->event2);

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                yield $this->event2;

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                $this->assertFalse($this->store2->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2], iterator_to_array($this->store2->stream()));

                $store->add($this->event3);

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                $this->assertFalse($this->store2->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2], iterator_to_array($this->store2->stream()));

                yield $this->event3;

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                $this->assertFalse($this->store2->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2], iterator_to_array($this->store2->stream()));
            })
        ;

        $this->assertTrue($this->store1->stream()->empty());
        $this->assertTrue($this->store2->stream()->empty());

        $events = $subscription->subscribeTo($this->store1);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1, $this->event2, $this->event3], $events);
        $this->assertFalse($this->store1->stream()->empty());
        $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store1->stream()));
        $this->assertFalse($this->store2->stream()->empty());
        $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store2->stream()));
    }

    public function testSubscriberForMultipleEventsTransactionLimit2()
    {
        $subscription = new DbalTransactionalSubscription($this->subscription, self::$connection1, 3);

        $this->subscription
            ->expects($this->once())
            ->method('subscribeTo')
            ->with($this->store1, null)
            ->willReturnCallback(function (EventStore $store) {
                $this->assertTrue($store->stream()->empty());
                $this->assertTrue($this->store2->stream()->empty());

                $store->add($this->event1);

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                yield $this->event1;

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                $store->add($this->event2);

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                yield $this->event2;

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                $store->add($this->event3);

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                yield $this->event3;

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                $this->assertFalse($this->store2->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store2->stream()));
            })
        ;

        $this->assertTrue($this->store1->stream()->empty());
        $this->assertTrue($this->store2->stream()->empty());

        $events = $subscription->subscribeTo($this->store1);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1, $this->event2, $this->event3], $events);
        $this->assertFalse($this->store1->stream()->empty());
        $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store1->stream()));
        $this->assertFalse($this->store2->stream()->empty());
        $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store2->stream()));
    }

    public function testSubscriberForMultipleEventsTransactionLimit3()
    {
        $subscription = new DbalTransactionalSubscription($this->subscription, self::$connection1, 4);

        $this->subscription
            ->expects($this->once())
            ->method('subscribeTo')
            ->with($this->store1, null)
            ->willReturnCallback(function (EventStore $store) {
                $this->assertTrue($store->stream()->empty());
                $this->assertTrue($this->store2->stream()->empty());

                $store->add($this->event1);

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                yield $this->event1;

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                $store->add($this->event2);

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                yield $this->event2;

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                $store->add($this->event3);

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                yield $this->event3;

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());
            })
        ;

        $this->assertTrue($this->store1->stream()->empty());
        $this->assertTrue($this->store2->stream()->empty());

        $events = $subscription->subscribeTo($this->store1);
        $events = iterator_to_array($events);

        $this->assertEquals([$this->event1, $this->event2, $this->event3], $events);
        $this->assertFalse($this->store1->stream()->empty());
        $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store1->stream()));
        $this->assertFalse($this->store2->stream()->empty());
        $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store2->stream()));
    }

    public function testSubscriberForErrorDuringTransaction1()
    {
        $subscription = new DbalTransactionalSubscription($this->subscription, self::$connection1, 1);

        $this->subscription
            ->expects($this->once())
            ->method('subscribeTo')
            ->with($this->store1, null)
            ->willReturnCallback(function (EventStore $store) {
                $this->assertTrue($store->stream()->empty());
                $this->assertTrue($this->store2->stream()->empty());

                $store->add($this->event1);

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                yield $this->event1;

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1], iterator_to_array($store->stream()));
                $this->assertFalse($this->store2->stream()->empty());
                $this->assertEquals([$this->event1], iterator_to_array($this->store2->stream()));

                $store->add($this->event2);

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                $this->assertFalse($this->store2->stream()->empty());
                $this->assertEquals([$this->event1], iterator_to_array($this->store2->stream()));

                yield $this->event2;

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                $this->assertFalse($this->store2->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2], iterator_to_array($this->store2->stream()));

                $store->add($this->event3);

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                $this->assertFalse($this->store2->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2], iterator_to_array($this->store2->stream()));

                yield $this->event3;

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                $this->assertFalse($this->store2->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store2->stream()));

                throw new \RuntimeException('test');
            })
        ;

        $this->assertTrue($this->store1->stream()->empty());
        $this->assertTrue($this->store2->stream()->empty());

        $this->expectExceptionObject(new \RuntimeException('test'));

        try {
            $events = $subscription->subscribeTo($this->store1);
            $events->rewind();
            $event1 = $events->current();
            $this->assertEquals($this->event1, $event1);
            $events->next();
            $event2 = $events->current();
            $this->assertEquals($this->event2, $event2);
            $events->next();
            $event3 = $events->current();
            $this->assertEquals($this->event3, $event3);
            $events->next();
        } finally {
            $this->assertFalse($this->store1->stream()->empty());
            $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store1->stream()));
            $this->assertFalse($this->store2->stream()->empty());
            $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store2->stream()));
        }
    }

    public function testSubscriberForErrorDuringTransaction2()
    {
        $subscription = new DbalTransactionalSubscription($this->subscription, self::$connection1, 2);

        $this->subscription
            ->expects($this->once())
            ->method('subscribeTo')
            ->with($this->store1, null)
            ->willReturnCallback(function (EventStore $store) {
                $this->assertTrue($store->stream()->empty());
                $this->assertTrue($this->store2->stream()->empty());

                $store->add($this->event1);

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                yield $this->event1;

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                $store->add($this->event2);

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                yield $this->event2;

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                $this->assertFalse($this->store2->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2], iterator_to_array($this->store2->stream()));

                $store->add($this->event3);

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                $this->assertFalse($this->store2->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2], iterator_to_array($this->store2->stream()));

                yield $this->event3;

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                $this->assertFalse($this->store2->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2], iterator_to_array($this->store2->stream()));

                throw new \RuntimeException('test');
            })
        ;

        $this->assertTrue($this->store1->stream()->empty());
        $this->assertTrue($this->store2->stream()->empty());

        $this->expectExceptionObject(new \RuntimeException('test'));

        try {
            $events = $subscription->subscribeTo($this->store1);
            $events->rewind();
            $event1 = $events->current();
            $this->assertEquals($this->event1, $event1);
            $events->next();
            $event2 = $events->current();
            $this->assertEquals($this->event2, $event2);
            $events->next();
        } finally {
            $this->assertFalse($this->store1->stream()->empty());
            $this->assertEquals([$this->event1, $this->event2], iterator_to_array($this->store1->stream()));
            $this->assertFalse($this->store2->stream()->empty());
            $this->assertEquals([$this->event1, $this->event2], iterator_to_array($this->store2->stream()));
        }
    }

    public function testSubscriberForErrorDuringTransaction3()
    {
        $subscription = new DbalTransactionalSubscription($this->subscription, self::$connection1, 3);

        $this->subscription
            ->expects($this->once())
            ->method('subscribeTo')
            ->with($this->store1, null)
            ->willReturnCallback(function (EventStore $store) {
                $this->assertTrue($store->stream()->empty());
                $this->assertTrue($this->store2->stream()->empty());

                $store->add($this->event1);

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                yield $this->event1;

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                $store->add($this->event2);

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                yield $this->event2;

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                $store->add($this->event3);

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                yield $this->event3;

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                $this->assertFalse($this->store2->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store2->stream()));

                throw new \RuntimeException('test');
            })
        ;

        $this->assertTrue($this->store1->stream()->empty());
        $this->assertTrue($this->store2->stream()->empty());

        $this->expectExceptionObject(new \RuntimeException('test'));

        try {
            $events = $subscription->subscribeTo($this->store1);
            $events->rewind();
            $event1 = $events->current();
            $this->assertEquals($this->event1, $event1);
            $events->next();
            $event2 = $events->current();
            $this->assertEquals($this->event2, $event2);
            $events->next();
            $event3 = $events->current();
            $this->assertEquals($this->event3, $event3);
            $events->next();
        } finally {
            $this->assertFalse($this->store1->stream()->empty());
            $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store1->stream()));
            $this->assertFalse($this->store2->stream()->empty());
            $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store2->stream()));
        }
    }

    public function testSubscriberForErrorDuringTransaction4()
    {
        $subscription = new DbalTransactionalSubscription($this->subscription, self::$connection1, 4);

        $this->subscription
            ->expects($this->once())
            ->method('subscribeTo')
            ->with($this->store1, null)
            ->willReturnCallback(function (EventStore $store) {
                $this->assertTrue($store->stream()->empty());
                $this->assertTrue($this->store2->stream()->empty());

                $store->add($this->event1);

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                yield $this->event1;

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                $store->add($this->event2);

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                yield $this->event2;

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                $store->add($this->event3);

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                yield $this->event3;

                $this->assertFalse($store->stream()->empty());
                $this->assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                $this->assertTrue($this->store2->stream()->empty());

                throw new \RuntimeException('test');
            })
        ;

        $this->assertTrue($this->store1->stream()->empty());
        $this->assertTrue($this->store2->stream()->empty());

        $this->expectExceptionObject(new \RuntimeException('test'));

        try {
            $events = $subscription->subscribeTo($this->store1);
            $events->rewind();
        } finally {
            $this->assertTrue($this->store1->stream()->empty());
            $this->assertTrue($this->store2->stream()->empty());
        }
    }
}

namespace Streak\Infrastructure\Event\Subscription\DbalTransactionalSubscriptionTest;

use Streak\Domain\Event;
use Streak\Domain\Id\UUID;

class ProducerId1 extends UUID
{
}

class Event1 implements Event
{
}

class Event2 implements Event
{
}

class Event3 implements Event
{
}
