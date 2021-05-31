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

namespace Streak\Infrastructure\Domain\Event\Subscription;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Application\Event\Listener;
use Streak\Application\Event\Listener\Subscription;
use Streak\Domain\EventStore;
use Streak\Domain\Id\UUID;
use Streak\Infrastructure\Domain\Event\Converter\NestedObjectConverter;
use Streak\Infrastructure\Domain\Event\Subscription\DbalTransactionalSubscriptionTest\Event1;
use Streak\Infrastructure\Domain\Event\Subscription\DbalTransactionalSubscriptionTest\Event2;
use Streak\Infrastructure\Domain\Event\Subscription\DbalTransactionalSubscriptionTest\Event3;
use Streak\Infrastructure\Domain\Event\Subscription\DbalTransactionalSubscriptionTest\ProducerId1;
use Streak\Infrastructure\Domain\EventStore\DbalPostgresEventStore;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\Event\Subscription\DbalTransactionalSubscription
 */
class DbalTransactionalSubscriptionTest extends TestCase
{
    private Subscription $subscription;

    private Listener $listener;

    private EventStore $store1;
    private EventStore $store2;

    private Listener\Id $subscriptionId;

    private UUID $producerId1;

    private Event\Envelope $event1;
    private Event\Envelope $event2;
    private Event\Envelope $event3;

    private Connection $connection;
    private static Connection $connection1;
    private static Connection $connection2;

    public static function setUpBeforeClass(): void
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

    protected function setUp(): void
    {
        $this->subscription = $this->getMockBuilder(Subscription::class)->getMockForAbstractClass();
        $this->listener = $this->getMockBuilder(Listener::class)->getMockForAbstractClass();
        $this->subscriptionId = $this->getMockBuilder(Listener\Id::class)->getMockForAbstractClass();
        $this->connection = $this->getMockBuilder(Connection::class)->getMockForAbstractClass();
        $this->producerId1 = ProducerId1::random();
        $this->event1 = Event\Envelope::new(new Event1(), $this->producerId1);
        $this->event2 = Event\Envelope::new(new Event2(), $this->producerId1);
        $this->event3 = Event\Envelope::new(new Event3(), $this->producerId1);

        self::$connection1->close();
        self::$connection1->connect();
        self::$connection2->close();
        self::$connection2->connect();

        $this->store1 = new DbalPostgresEventStore(self::$connection1, new NestedObjectConverter());
        $this->store1->drop();
        $this->store1->create();

        $this->store2 = new DbalPostgresEventStore(self::$connection2, new NestedObjectConverter());
    }

    public function testObject(): void
    {
        $subscription = new DbalTransactionalSubscription($this->subscription, $this->connection, 1);

        self::assertSame($this->subscription, $subscription->subscription());

        $this->subscription
            ->expects(self::once())
            ->method('listener')
            ->with()
            ->willReturn($this->listener)
        ;

        self::assertSame($this->listener, $subscription->listener());

        $this->subscription
            ->expects(self::exactly(2))
            ->method('version')
            ->with()
            ->willReturnOnConsecutiveCalls(1000, 1001)
        ;

        self::assertSame(1000, $subscription->version());
        self::assertSame(1001, $subscription->version());

        $this->subscription
            ->expects(self::once())
            ->method('subscriptionId')
            ->with()
            ->willReturn($this->subscriptionId)
        ;

        self::assertSame($this->subscriptionId, $subscription->subscriptionId());

        $this->subscription
            ->expects(self::once())
            ->method('startFor')
            ->with($this->event1)
        ;

        $subscription->startFor($this->event1);

        $this->subscription
            ->expects(self::once())
            ->method('restart')
            ->with()
        ;

        $subscription->restart();

        $this->subscription
            ->expects(self::exactly(2))
            ->method('starting')
            ->with()
            ->willReturnOnConsecutiveCalls(true, false)
        ;

        self::assertTrue($subscription->starting());
        self::assertFalse($subscription->starting());

        $this->subscription
            ->expects(self::exactly(2))
            ->method('started')
            ->with()
            ->willReturnOnConsecutiveCalls(true, false)
        ;

        self::assertTrue($subscription->started());
        self::assertFalse($subscription->started());

        $this->subscription
            ->expects(self::exactly(2))
            ->method('completed')
            ->with()
            ->willReturnOnConsecutiveCalls(false, true)
        ;

        self::assertFalse($subscription->completed());
        self::assertTrue($subscription->completed());

        $this->subscription
            ->expects(self::exactly(2))
            ->method('paused')
            ->with()
            ->willReturnOnConsecutiveCalls(false, true)
        ;

        self::assertFalse($subscription->paused());
        self::assertTrue($subscription->paused());

        $this->subscription
            ->expects(self::once())
            ->method('pause')
            ->with()
        ;

        $subscription->pause();

        $this->subscription
            ->expects(self::once())
            ->method('unpause')
            ->with()
        ;

        $subscription->unpause();
    }

    public function testSubscriberForSingleEventTransaction(): void
    {
        $subscription = new DbalTransactionalSubscription($this->subscription, self::$connection1, 1);

        $this->subscription
            ->expects(self::once())
            ->method('subscribeTo')
            ->with($this->store1, null)
            ->willReturnCallback(function (EventStore $store) {
                self::assertTrue($store->stream()->empty());
                self::assertTrue($this->store2->stream()->empty());

                $store->add($this->event1);

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                yield $this->event1;

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1], iterator_to_array($store->stream()));
                self::assertFalse($this->store2->stream()->empty());
                self::assertEquals([$this->event1], iterator_to_array($this->store2->stream()));

                $store->add($this->event2);

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                self::assertFalse($this->store2->stream()->empty());
                self::assertEquals([$this->event1], iterator_to_array($this->store2->stream()));

                yield $this->event2;

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                self::assertFalse($this->store2->stream()->empty());
                self::assertEquals([$this->event1, $this->event2], iterator_to_array($this->store2->stream()));

                $store->add($this->event3);

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                self::assertFalse($this->store2->stream()->empty());
                self::assertEquals([$this->event1, $this->event2], iterator_to_array($this->store2->stream()));

                yield $this->event3;

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                self::assertFalse($this->store2->stream()->empty());
                self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store2->stream()));
            })
        ;

        self::assertTrue($this->store1->stream()->empty());
        self::assertTrue($this->store2->stream()->empty());

        $events = $subscription->subscribeTo($this->store1);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event1, $this->event2, $this->event3], $events);
        self::assertFalse($this->store1->stream()->empty());
        self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store1->stream()));
        self::assertFalse($this->store2->stream()->empty());
        self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store2->stream()));
    }

    public function testSubscriberForMultipleEventsTransactionLimit1(): void
    {
        $subscription = new DbalTransactionalSubscription($this->subscription, self::$connection1, 2);

        $this->subscription
            ->expects(self::once())
            ->method('subscribeTo')
            ->with($this->store1, null)
            ->willReturnCallback(function (EventStore $store) {
                self::assertTrue($store->stream()->empty());
                self::assertTrue($this->store2->stream()->empty());

                $store->add($this->event1);

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                yield $this->event1;

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                $store->add($this->event2);

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                yield $this->event2;

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                self::assertFalse($this->store2->stream()->empty());
                self::assertEquals([$this->event1, $this->event2], iterator_to_array($this->store2->stream()));

                $store->add($this->event3);

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                self::assertFalse($this->store2->stream()->empty());
                self::assertEquals([$this->event1, $this->event2], iterator_to_array($this->store2->stream()));

                yield $this->event3;

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                self::assertFalse($this->store2->stream()->empty());
                self::assertEquals([$this->event1, $this->event2], iterator_to_array($this->store2->stream()));
            })
        ;

        self::assertTrue($this->store1->stream()->empty());
        self::assertTrue($this->store2->stream()->empty());

        $events = $subscription->subscribeTo($this->store1);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event1, $this->event2, $this->event3], $events);
        self::assertFalse($this->store1->stream()->empty());
        self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store1->stream()));
        self::assertFalse($this->store2->stream()->empty());
        self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store2->stream()));
    }

    public function testSubscriberForMultipleEventsTransactionLimit2(): void
    {
        $subscription = new DbalTransactionalSubscription($this->subscription, self::$connection1, 3);

        $this->subscription
            ->expects(self::once())
            ->method('subscribeTo')
            ->with($this->store1, null)
            ->willReturnCallback(function (EventStore $store) {
                self::assertTrue($store->stream()->empty());
                self::assertTrue($this->store2->stream()->empty());

                $store->add($this->event1);

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                yield $this->event1;

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                $store->add($this->event2);

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                yield $this->event2;

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                $store->add($this->event3);

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                yield $this->event3;

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                self::assertFalse($this->store2->stream()->empty());
                self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store2->stream()));
            })
        ;

        self::assertTrue($this->store1->stream()->empty());
        self::assertTrue($this->store2->stream()->empty());

        $events = $subscription->subscribeTo($this->store1);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event1, $this->event2, $this->event3], $events);
        self::assertFalse($this->store1->stream()->empty());
        self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store1->stream()));
        self::assertFalse($this->store2->stream()->empty());
        self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store2->stream()));
    }

    public function testSubscriberForMultipleEventsTransactionLimit3(): void
    {
        $subscription = new DbalTransactionalSubscription($this->subscription, self::$connection1, 4);

        $this->subscription
            ->expects(self::once())
            ->method('subscribeTo')
            ->with($this->store1, null)
            ->willReturnCallback(function (EventStore $store) {
                self::assertTrue($store->stream()->empty());
                self::assertTrue($this->store2->stream()->empty());

                $store->add($this->event1);

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                yield $this->event1;

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                $store->add($this->event2);

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                yield $this->event2;

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                $store->add($this->event3);

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                yield $this->event3;

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());
            })
        ;

        self::assertTrue($this->store1->stream()->empty());
        self::assertTrue($this->store2->stream()->empty());

        $events = $subscription->subscribeTo($this->store1);
        $events = iterator_to_array($events);

        self::assertEquals([$this->event1, $this->event2, $this->event3], $events);
        self::assertFalse($this->store1->stream()->empty());
        self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store1->stream()));
        self::assertFalse($this->store2->stream()->empty());
        self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store2->stream()));
    }

    public function testSubscriberForErrorDuringTransaction1(): void
    {
        $subscription = new DbalTransactionalSubscription($this->subscription, self::$connection1, 1);

        $this->subscription
            ->expects(self::once())
            ->method('subscribeTo')
            ->with($this->store1, null)
            ->willReturnCallback(function (EventStore $store) {
                self::assertTrue($store->stream()->empty());
                self::assertTrue($this->store2->stream()->empty());

                $store->add($this->event1);

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                yield $this->event1;

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1], iterator_to_array($store->stream()));
                self::assertFalse($this->store2->stream()->empty());
                self::assertEquals([$this->event1], iterator_to_array($this->store2->stream()));

                $store->add($this->event2);

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                self::assertFalse($this->store2->stream()->empty());
                self::assertEquals([$this->event1], iterator_to_array($this->store2->stream()));

                yield $this->event2;

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                self::assertFalse($this->store2->stream()->empty());
                self::assertEquals([$this->event1, $this->event2], iterator_to_array($this->store2->stream()));

                $store->add($this->event3);

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                self::assertFalse($this->store2->stream()->empty());
                self::assertEquals([$this->event1, $this->event2], iterator_to_array($this->store2->stream()));

                yield $this->event3;

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                self::assertFalse($this->store2->stream()->empty());
                self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store2->stream()));

                throw new \RuntimeException('test');
            })
        ;

        self::assertTrue($this->store1->stream()->empty());
        self::assertTrue($this->store2->stream()->empty());

        $this->expectExceptionObject(new \RuntimeException('test'));

        try {
            $events = $subscription->subscribeTo($this->store1);
            $events->rewind();
            $event1 = $events->current();
            self::assertEquals($this->event1, $event1);
            $events->next();
            $event2 = $events->current();
            self::assertEquals($this->event2, $event2);
            $events->next();
            $event3 = $events->current();
            self::assertEquals($this->event3, $event3);
            $events->next();
        } finally {
            self::assertFalse($this->store1->stream()->empty());
            self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store1->stream()));
            self::assertFalse($this->store2->stream()->empty());
            self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store2->stream()));
        }
    }

    public function testSubscriberForErrorDuringTransaction2(): void
    {
        $subscription = new DbalTransactionalSubscription($this->subscription, self::$connection1, 2);

        $this->subscription
            ->expects(self::once())
            ->method('subscribeTo')
            ->with($this->store1, null)
            ->willReturnCallback(function (EventStore $store) {
                self::assertTrue($store->stream()->empty());
                self::assertTrue($this->store2->stream()->empty());

                $store->add($this->event1);

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                yield $this->event1;

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                $store->add($this->event2);

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                yield $this->event2;

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                self::assertFalse($this->store2->stream()->empty());
                self::assertEquals([$this->event1, $this->event2], iterator_to_array($this->store2->stream()));

                $store->add($this->event3);

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                self::assertFalse($this->store2->stream()->empty());
                self::assertEquals([$this->event1, $this->event2], iterator_to_array($this->store2->stream()));

                yield $this->event3;

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                self::assertFalse($this->store2->stream()->empty());
                self::assertEquals([$this->event1, $this->event2], iterator_to_array($this->store2->stream()));

                throw new \RuntimeException('test');
            })
        ;

        self::assertTrue($this->store1->stream()->empty());
        self::assertTrue($this->store2->stream()->empty());

        $this->expectExceptionObject(new \RuntimeException('test'));

        try {
            $events = $subscription->subscribeTo($this->store1);
            $events->rewind();
            $event1 = $events->current();
            self::assertEquals($this->event1, $event1);
            $events->next();
            $event2 = $events->current();
            self::assertEquals($this->event2, $event2);
            $events->next();
        } finally {
            self::assertFalse($this->store1->stream()->empty());
            self::assertEquals([$this->event1, $this->event2], iterator_to_array($this->store1->stream()));
            self::assertFalse($this->store2->stream()->empty());
            self::assertEquals([$this->event1, $this->event2], iterator_to_array($this->store2->stream()));
        }
    }

    public function testSubscriberForErrorDuringTransaction3(): void
    {
        $subscription = new DbalTransactionalSubscription($this->subscription, self::$connection1, 3);

        $this->subscription
            ->expects(self::once())
            ->method('subscribeTo')
            ->with($this->store1, null)
            ->willReturnCallback(function (EventStore $store) {
                self::assertTrue($store->stream()->empty());
                self::assertTrue($this->store2->stream()->empty());

                $store->add($this->event1);

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                yield $this->event1;

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                $store->add($this->event2);

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                yield $this->event2;

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                $store->add($this->event3);

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                yield $this->event3;

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                self::assertFalse($this->store2->stream()->empty());
                self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store2->stream()));

                throw new \RuntimeException('test');
            })
        ;

        self::assertTrue($this->store1->stream()->empty());
        self::assertTrue($this->store2->stream()->empty());

        $this->expectExceptionObject(new \RuntimeException('test'));

        try {
            $events = $subscription->subscribeTo($this->store1);
            $events->rewind();
            $event1 = $events->current();
            self::assertEquals($this->event1, $event1);
            $events->next();
            $event2 = $events->current();
            self::assertEquals($this->event2, $event2);
            $events->next();
            $event3 = $events->current();
            self::assertEquals($this->event3, $event3);
            $events->next();
        } finally {
            self::assertFalse($this->store1->stream()->empty());
            self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store1->stream()));
            self::assertFalse($this->store2->stream()->empty());
            self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($this->store2->stream()));
        }
    }

    public function testSubscriberForErrorDuringTransaction4(): void
    {
        $subscription = new DbalTransactionalSubscription($this->subscription, self::$connection1, 4);

        $this->subscription
            ->expects(self::once())
            ->method('subscribeTo')
            ->with($this->store1, null)
            ->willReturnCallback(function (EventStore $store) {
                self::assertTrue($store->stream()->empty());
                self::assertTrue($this->store2->stream()->empty());

                $store->add($this->event1);

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                yield $this->event1;

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                $store->add($this->event2);

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                yield $this->event2;

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                $store->add($this->event3);

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                yield $this->event3;

                self::assertFalse($store->stream()->empty());
                self::assertEquals([$this->event1, $this->event2, $this->event3], iterator_to_array($store->stream()));
                self::assertTrue($this->store2->stream()->empty());

                throw new \RuntimeException('test');
            })
        ;

        self::assertTrue($this->store1->stream()->empty());
        self::assertTrue($this->store2->stream()->empty());

        $this->expectExceptionObject(new \RuntimeException('test'));

        try {
            $events = $subscription->subscribeTo($this->store1);
            $events->rewind();
        } finally {
            self::assertTrue($this->store1->stream()->empty());
            self::assertTrue($this->store2->stream()->empty());
        }
    }
}

namespace Streak\Infrastructure\Domain\Event\Subscription\DbalTransactionalSubscriptionTest;

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
