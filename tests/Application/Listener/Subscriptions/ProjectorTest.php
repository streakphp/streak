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

namespace Streak\Application\Listener\Subscriptions;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Statement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Application\Listener\Subscriptions\Projector\Query\ListSubscriptions;
use Streak\Domain\Event;
use Streak\Domain\Event\Metadata;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionCompleted;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionIgnoredEvent;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionListenedToEvent;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionRestarted;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionStarted;
use Streak\Domain\Id\UUID;
use Streak\Infrastructure\EventStore\DbalPostgresEventStore;
use Streak\Infrastructure\EventStore\InMemoryEventStore;
use Streak\Infrastructure\FixedClock;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Application\Listener\Subscriptions\Projector
 * @covers \Streak\Application\Listener\Subscriptions\Projector\Query\ListSubscriptions
 */
class ProjectorTest extends TestCase
{
    private const UUIDS = [
        100 => '0aa285f1-be7b-41b3-a157-4ffe276aa291',
        101 => '53cfe65b-60e2-4c93-8e34-28e508253f67',
        102 => 'f6ba7486-7681-4eb0-9b64-aaee720f88f6',
        103 => '23352922-d979-4cdd-902d-d900d82bb8e2',
        104 => '6dab469e-19e4-47af-8f50-cc8dd46d220b',
        105 => 'ce42355f-8b7b-453c-9c23-11a57acfb267',
        106 => '5130e7b9-dd94-483a-8b8c-b86a41edee52',
        107 => '979970e0-1bd5-47fd-b768-7960e4000072',
        108 => 'ab93c170-0069-4e84-87bb-99b11d90d7ea',
        109 => '692e63cb-4304-4113-9fd9-f8363b25662b',
    ];

    /**
     * @var Connection
     */
    private static $postgres;

    /**
     * @var FixedClock
     */
    private $clock;

    /**
     * @var Event|MockObject
     */
    private $event;

    /**
     * @var Connection|MockObject
     */
    private $connection;

    /**
     * @var Event\Stream|MockObject
     */
    private $stream;

    /**
     * @var Statement|MockObject
     */
    private $statement;

    public static function setUpBeforeClass()
    {
        self::$postgres = DriverManager::getConnection([
            'driver' => 'pdo_pgsql',
            'host' => $_ENV['PHPUNIT_POSTGRES_HOSTNAME'],
            'port' => (int) $_ENV['PHPUNIT_POSTGRES_PORT'],
            'dbname' => $_ENV['PHPUNIT_POSTGRES_DATABASE'],
            'user' => $_ENV['PHPUNIT_POSTGRES_USERNAME'],
            'password' => $_ENV['PHPUNIT_POSTGRES_PASSWORD'],
        ]);
    }

    public function setUp()
    {
        $this->clock = new FixedClock(new \DateTimeImmutable('2018-01-01T00:00:00+00:00'));
        $this->event = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $this->event = Event\Envelope::new($this->event, UUID::random());
        $this->connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
        $this->stream = $this->getMockBuilder(Event\Stream::class)->getMockForAbstractClass();
        $this->statement = $this->getMockBuilder(Statement::class)->disableOriginalConstructor()->getMock();
    }

    public function testProjector()
    {
        $id = new Projector\Id();
        $projector = new Projector($id, $this::$postgres, $this->clock);

        $this->stream
            ->expects($this->once())
            ->method('withEventsOfType')
            ->with(SubscriptionStarted::class, SubscriptionRestarted::class, SubscriptionListenedToEvent::class, SubscriptionIgnoredEvent::class, SubscriptionCompleted::class)
            ->willReturnSelf()
        ;

        $this->stream
            ->expects($this->once())
            ->method('withoutEventsProducedBy')
            ->with($id)
            ->willReturnSelf()
        ;

        // TODO: add test with in memory stream and actually check if filter() works.
        $projector->filter($this->stream);

        $this->assertSame($id, $projector->id());

        $projector->reset();

        $subscriptions = $projector->handleQuery(new ListSubscriptions());

        $this->assertCount(1, $subscriptions);
        $this->assertEquals([
            'subscription_type' => 'Streak\Application\Listener\Subscriptions\Projector\Id',
            'subscription_id' => 'e25e531b-1d6f-4881-b9d4-ad99268a3122',
            'subscription_version' => 1,
            'last_event_uuid' => '00000000-0000-0000-0000-000000000000',
            'last_event_at' => new \DateTime('1970-01-01T01:00:00+01:00'),
            'is_completed' => false,
            'last_sync_at' => new \DateTime('2018-01-01T00:00:00+00:00'),
        ], $subscriptions[0]);

        $this->clock->timeIs(new \DateTime('2018-01-01T00:00:01+00:00'));

        $event = new SubscriptionStarted($this->event, new \DateTime('2018-02-01T00:00:00+00:00'));
        $event = (new Event\Envelope(new UUID(self::UUIDS[100]), 'name', $event, $id))->set(DbalPostgresEventStore::EVENT_ATTRIBUTE_NUMBER, 100);

        $projector->on($event);

        $subscriptions = $projector->handleQuery(new ListSubscriptions());

        $this->assertCount(2, $subscriptions);
        $this->assertEquals([
            'subscription_type' => 'Streak\Application\Listener\Subscriptions\ProjectorTest\Id',
            'subscription_id' => '3f79b9cb-d7d5-4782-9de2-52dd3f6ee706',
            'subscription_version' => 1,
            'last_event_uuid' => self::UUIDS[100],
            'last_event_at' => new \DateTime('2018-02-01T01:00:00+01:00'),
            'is_completed' => false,
            'last_sync_at' => new \DateTime('2018-01-01T00:00:01+00:00'),
        ], $subscriptions[0]);
        $this->assertEquals([
            'subscription_type' => 'Streak\Application\Listener\Subscriptions\Projector\Id',
            'subscription_id' => 'e25e531b-1d6f-4881-b9d4-ad99268a3122',
            'subscription_version' => 2,
            'last_event_uuid' => self::UUIDS[100],
            'last_event_at' => new \DateTime('2018-02-01T02:00:00+02:00'),
            'is_completed' => false,
            'last_sync_at' => new \DateTime('2018-01-01T00:00:01+00:00'),
        ], $subscriptions[1]);

        $subscriptions = $projector->handleQuery(new ListSubscriptions([
            'Streak\Application\Listener\Subscriptions\Projector\Id',
            'Streak\Application\Listener\Subscriptions\ProjectorTest\Id',
        ]));

        $this->assertCount(2, $subscriptions);
        $this->assertEquals([
            'subscription_type' => 'Streak\Application\Listener\Subscriptions\ProjectorTest\Id',
            'subscription_id' => '3f79b9cb-d7d5-4782-9de2-52dd3f6ee706',
            'subscription_version' => 1,
            'last_event_uuid' => self::UUIDS[100],
            'last_event_at' => new \DateTime('2018-02-01T01:00:00+01:00'),
            'is_completed' => false,
            'last_sync_at' => new \DateTime('2018-01-01T00:00:01+00:00'),
        ], $subscriptions[0]);
        $this->assertEquals([
            'subscription_type' => 'Streak\Application\Listener\Subscriptions\Projector\Id',
            'subscription_id' => 'e25e531b-1d6f-4881-b9d4-ad99268a3122',
            'subscription_version' => 2,
            'last_event_uuid' => self::UUIDS[100],
            'last_event_at' => new \DateTime('2018-02-01T02:00:00+02:00'),
            'is_completed' => false,
            'last_sync_at' => new \DateTime('2018-01-01T00:00:01+00:00'),
        ], $subscriptions[1]);

        $subscriptions = $projector->handleQuery(new ListSubscriptions([
            'Streak\Application\Listener\Subscriptions\ProjectorTest\Id',
        ]));

        $this->assertCount(1, $subscriptions);
        $this->assertEquals([
            'subscription_type' => 'Streak\Application\Listener\Subscriptions\ProjectorTest\Id',
            'subscription_id' => '3f79b9cb-d7d5-4782-9de2-52dd3f6ee706',
            'subscription_version' => 1,
            'last_event_uuid' => self::UUIDS[100],
            'last_event_at' => new \DateTime('2018-02-01T01:00:00+01:00'),
            'is_completed' => false,
            'last_sync_at' => new \DateTime('2018-01-01T00:00:01+00:00'),
        ], $subscriptions[0]);

        $subscriptions = $projector->handleQuery(new ListSubscriptions([
            'Streak\Application\Listener\Subscriptions\Projector\Id',
        ]));

        $this->assertCount(1, $subscriptions);
        $this->assertEquals([
            'subscription_type' => 'Streak\Application\Listener\Subscriptions\Projector\Id',
            'subscription_id' => 'e25e531b-1d6f-4881-b9d4-ad99268a3122',
            'subscription_version' => 2,
            'last_event_uuid' => self::UUIDS[100],
            'last_event_at' => new \DateTime('2018-02-01T02:00:00+02:00'),
            'is_completed' => false,
            'last_sync_at' => new \DateTime('2018-01-01T00:00:01+00:00'),
        ], $subscriptions[0]);

        $this->clock->timeIs(new \DateTime('2018-01-01T00:00:02+00:00'));

        $event = new SubscriptionListenedToEvent($this->event, 2, new \DateTime('2018-02-01T00:00:01+00:00'));
        Metadata::fromArray([
            'producer_type' => 'Streak\Application\Listener\Subscriptions\ProjectorTest\Id',
            'producer_id' => '3f79b9cb-d7d5-4782-9de2-52dd3f6ee706',
            'sequence' => '101',
            'uuid' => self::UUIDS[101],
        ])->toObject($event);

        $projector->on($event);

        $subscriptions = $projector->handleQuery(new ListSubscriptions());

        $this->assertCount(2, $subscriptions);
        $this->assertEquals([
            'subscription_type' => 'Streak\Application\Listener\Subscriptions\ProjectorTest\Id',
            'subscription_id' => '3f79b9cb-d7d5-4782-9de2-52dd3f6ee706',
            'subscription_version' => 2,
            'last_event_uuid' => self::UUIDS[101],
            'last_event_at' => new \DateTime('2018-02-01T01:00:01+01:00'),
            'is_completed' => false,
            'last_sync_at' => new \DateTime('2018-01-01T00:00:02+00:00'),
        ], $subscriptions[0]);
        $this->assertEquals([
            'subscription_type' => 'Streak\Application\Listener\Subscriptions\Projector\Id',
            'subscription_id' => 'e25e531b-1d6f-4881-b9d4-ad99268a3122',
            'subscription_version' => 3,
            'last_event_uuid' => self::UUIDS[101],
            'last_event_at' => new \DateTime('2018-02-01T02:00:01+02:00'),
            'is_completed' => false,
            'last_sync_at' => new \DateTime('2018-01-01T00:00:02+00:00'),
        ], $subscriptions[1]);

        $this->clock->timeIs(new \DateTime('2018-01-01T00:00:03+00:00'));

        $event = new SubscriptionIgnoredEvent($this->event, 3, new \DateTime('2018-02-01T00:00:02+00:00'));
        Metadata::fromArray([
            'producer_type' => 'Streak\Application\Listener\Subscriptions\ProjectorTest\Id',
            'producer_id' => '3f79b9cb-d7d5-4782-9de2-52dd3f6ee706',
            'sequence' => '102',
            'uuid' => self::UUIDS[102],
        ])->toObject($event);

        $projector->on($event);

        $subscriptions = $projector->handleQuery(new ListSubscriptions());

        $this->assertCount(2, $subscriptions);
        $this->assertEquals([
            'subscription_type' => 'Streak\Application\Listener\Subscriptions\ProjectorTest\Id',
            'subscription_id' => '3f79b9cb-d7d5-4782-9de2-52dd3f6ee706',
            'subscription_version' => 3,
            'last_event_uuid' => self::UUIDS[102],
            'last_event_at' => new \DateTime('2018-02-01T01:00:02+01:00'),
            'is_completed' => false,
            'last_sync_at' => new \DateTime('2018-01-01T00:00:03+00:00'),
        ], $subscriptions[0]);
        $this->assertEquals([
            'subscription_type' => 'Streak\Application\Listener\Subscriptions\Projector\Id',
            'subscription_id' => 'e25e531b-1d6f-4881-b9d4-ad99268a3122',
            'subscription_version' => 4,
            'last_event_uuid' => self::UUIDS[102],
            'last_event_at' => new \DateTime('2018-02-01T02:00:02+02:00'),
            'is_completed' => false,
            'last_sync_at' => new \DateTime('2018-01-01T00:00:03+00:00'),
        ], $subscriptions[1]);

        $this->clock->timeIs(new \DateTime('2018-01-01T00:00:04+00:00'));

        $event = new SubscriptionCompleted(4, new \DateTime('2018-02-01T00:00:03+00:00'));
        Metadata::fromArray([
            'producer_type' => 'Streak\Application\Listener\Subscriptions\ProjectorTest\Id',
            'producer_id' => '3f79b9cb-d7d5-4782-9de2-52dd3f6ee706',
            'sequence' => '103',
            'uuid' => self::UUIDS[103],
        ])->toObject($event);

        $projector->on($event);

        $subscriptions = $projector->handleQuery(new ListSubscriptions());

        $this->assertCount(2, $subscriptions);
        $this->assertEquals([
            'subscription_type' => 'Streak\Application\Listener\Subscriptions\ProjectorTest\Id',
            'subscription_id' => '3f79b9cb-d7d5-4782-9de2-52dd3f6ee706',
            'subscription_version' => 4,
            'last_event_uuid' => self::UUIDS[103],
            'last_event_at' => new \DateTime('2018-02-01T01:00:03+01:00'),
            'is_completed' => true,
            'last_sync_at' => new \DateTime('2018-01-01T00:00:04+00:00'),
        ], $subscriptions[0]);
        $this->assertEquals([
            'subscription_type' => 'Streak\Application\Listener\Subscriptions\Projector\Id',
            'subscription_id' => 'e25e531b-1d6f-4881-b9d4-ad99268a3122',
            'subscription_version' => 5,
            'last_event_uuid' => self::UUIDS[103],
            'last_event_at' => new \DateTime('2018-02-01T02:00:03+02:00'),
            'is_completed' => false,
            'last_sync_at' => new \DateTime('2018-01-01T00:00:04+00:00'),
        ], $subscriptions[1]);

        $subscriptions = $projector->handleQuery(new ListSubscriptions(null, true));

        $this->assertCount(1, $subscriptions);
        $this->assertEquals([
            'subscription_type' => 'Streak\Application\Listener\Subscriptions\ProjectorTest\Id',
            'subscription_id' => '3f79b9cb-d7d5-4782-9de2-52dd3f6ee706',
            'subscription_version' => 4,
            'last_event_uuid' => self::UUIDS[103],
            'last_event_at' => new \DateTime('2018-02-01T01:00:03+01:00'),
            'is_completed' => true,
            'last_sync_at' => new \DateTime('2018-01-01T00:00:04+00:00'),
        ], $subscriptions[0]);

        $subscriptions = $projector->handleQuery(new ListSubscriptions(null, false));

        $this->assertCount(1, $subscriptions);
        $this->assertEquals([
            'subscription_type' => 'Streak\Application\Listener\Subscriptions\Projector\Id',
            'subscription_id' => 'e25e531b-1d6f-4881-b9d4-ad99268a3122',
            'subscription_version' => 5,
            'last_event_uuid' => self::UUIDS[103],
            'last_event_at' => new \DateTime('2018-02-01T02:00:03+02:00'),
            'is_completed' => false,
            'last_sync_at' => new \DateTime('2018-01-01T00:00:04+00:00'),
        ], $subscriptions[0]);

        $this->clock->timeIs(new \DateTime('2018-01-01T00:00:05+00:00'));

        $event = new SubscriptionRestarted($this->event, 5, new \DateTime('2018-02-01T00:00:04+00:00'));
        Metadata::fromArray([
            'producer_type' => 'Streak\Application\Listener\Subscriptions\ProjectorTest\Id',
            'producer_id' => '3f79b9cb-d7d5-4782-9de2-52dd3f6ee706',
            'sequence' => '104',
            'uuid' => self::UUIDS[104],
        ])->toObject($event);

        $projector->on($event);

        $subscriptions = $projector->handleQuery(new ListSubscriptions());

        $this->assertCount(2, $subscriptions);
        $this->assertEquals([
            'subscription_type' => 'Streak\Application\Listener\Subscriptions\ProjectorTest\Id',
            'subscription_id' => '3f79b9cb-d7d5-4782-9de2-52dd3f6ee706',
            'subscription_version' => 5,
            'last_event_uuid' => self::UUIDS[104],
            'last_event_at' => new \DateTime('2018-02-01T01:00:04+01:00'),
            'is_completed' => false,
            'last_sync_at' => new \DateTime('2018-01-01T00:00:05+00:00'),
        ], $subscriptions[0]);
        $this->assertEquals([
            'subscription_type' => 'Streak\Application\Listener\Subscriptions\Projector\Id',
            'subscription_id' => 'e25e531b-1d6f-4881-b9d4-ad99268a3122',
            'subscription_version' => 6,
            'last_event_uuid' => self::UUIDS[104],
            'last_event_at' => new \DateTime('2018-02-01T02:00:04+02:00'),
            'is_completed' => false,
            'last_sync_at' => new \DateTime('2018-01-01T00:00:05+00:00'),
        ], $subscriptions[1]);

        $this->clock->timeIs(new \DateTime('2018-01-01T00:00:06+00:00'));

        $event = new SubscriptionListenedToEvent($this->event, 6, new \DateTime('2018-02-01T00:00:05+00:00'));
        Metadata::fromArray([
            'producer_type' => 'Streak\Application\Listener\Subscriptions\ProjectorTest\Id',
            'producer_id' => '3f79b9cb-d7d5-4782-9de2-52dd3f6ee706',
            'sequence' => '105',
            'uuid' => self::UUIDS[105],
        ])->toObject($event);

        $projector->on($event);

        $subscriptions = $projector->handleQuery(new ListSubscriptions());

        $this->assertCount(2, $subscriptions);
        $this->assertEquals([
            'subscription_type' => 'Streak\Application\Listener\Subscriptions\ProjectorTest\Id',
            'subscription_id' => '3f79b9cb-d7d5-4782-9de2-52dd3f6ee706',
            'subscription_version' => 6,
            'last_event_uuid' => self::UUIDS[105],
            'last_event_at' => new \DateTime('2018-02-01T01:00:05+01:00'),
            'is_completed' => false,
            'last_sync_at' => new \DateTime('2018-01-01T00:00:06+00:00'),
        ], $subscriptions[0]);
        $this->assertEquals([
            'subscription_type' => 'Streak\Application\Listener\Subscriptions\Projector\Id',
            'subscription_id' => 'e25e531b-1d6f-4881-b9d4-ad99268a3122',
            'subscription_version' => 7,
            'last_event_uuid' => self::UUIDS[105],
            'last_event_at' => new \DateTime('2018-02-01T02:00:05+02:00'),
            'is_completed' => false,
            'last_sync_at' => new \DateTime('2018-01-01T00:00:06+00:00'),
        ], $subscriptions[1]);

        $this->clock->timeIs(new \DateTime('2018-01-01T00:00:07+00:00'));

        $event = new SubscriptionCompleted(1, new \DateTime('2018-02-01T00:00:06+00:00'));
        Metadata::fromArray([
            'producer_type' => 'Streak\Application\Listener\Subscriptions\ProjectorTest\Id',
            'producer_id' => '3f79b9cb-d7d5-4782-9de2-52dd3f6ee706',
            'sequence' => '106',
            'uuid' => self::UUIDS[106],
        ])->toObject($event);

        $projector->on($event);

        $subscriptions = $projector->handleQuery(new ListSubscriptions());

        $this->assertCount(2, $subscriptions);
        $this->assertEquals([
            'subscription_type' => 'Streak\Application\Listener\Subscriptions\ProjectorTest\Id',
            'subscription_id' => '3f79b9cb-d7d5-4782-9de2-52dd3f6ee706',
            'subscription_version' => 7,
            'last_event_uuid' => self::UUIDS[106],
            'last_event_at' => new \DateTime('2018-02-01T01:00:06+01:00'),
            'is_completed' => true,
            'last_sync_at' => new \DateTime('2018-01-01T00:00:07+00:00'),
        ], $subscriptions[0]);
        $this->assertEquals([
            'subscription_type' => 'Streak\Application\Listener\Subscriptions\Projector\Id',
            'subscription_id' => 'e25e531b-1d6f-4881-b9d4-ad99268a3122',
            'subscription_version' => 8,
            'last_event_uuid' => self::UUIDS[106],
            'last_event_at' => new \DateTime('2018-02-01T02:00:06+02:00'),
            'is_completed' => false,
            'last_sync_at' => new \DateTime('2018-01-01T00:00:07+00:00'),
        ], $subscriptions[1]);

        $subscriptions = $projector->handleQuery(new ListSubscriptions());

        $this->assertCount(2, $subscriptions);
        $this->assertEquals([
            'subscription_type' => 'Streak\Application\Listener\Subscriptions\ProjectorTest\Id',
            'subscription_id' => '3f79b9cb-d7d5-4782-9de2-52dd3f6ee706',
            'subscription_version' => 7,
            'last_event_uuid' => self::UUIDS[106],
            'last_event_at' => new \DateTime('2018-02-01T01:00:06+01:00'),
            'is_completed' => true,
            'last_sync_at' => new \DateTime('2018-01-01T00:00:07+00:00'),
        ], $subscriptions[0]);
        $this->assertEquals([
            'subscription_type' => 'Streak\Application\Listener\Subscriptions\Projector\Id',
            'subscription_id' => 'e25e531b-1d6f-4881-b9d4-ad99268a3122',
            'subscription_version' => 8,
            'last_event_uuid' => self::UUIDS[106],
            'last_event_at' => new \DateTime('2018-02-01T02:00:06+02:00'),
            'is_completed' => false,
            'last_sync_at' => new \DateTime('2018-01-01T00:00:07+00:00'),
        ], $subscriptions[1]);
    }

    public function testPickingFirstEvent()
    {
        $event1 = new SubscriptionStarted($this->event, $this->clock->now());
        $event2 = new SubscriptionListenedToEvent($this->event, 1, $this->clock->now());
        $event3 = new SubscriptionIgnoredEvent($this->event, 1, $this->clock->now());
        $event4 = new SubscriptionCompleted(1, $this->clock->now());
        $event5 = new SubscriptionRestarted($this->event, 5, $this->clock->now());
        $event6 = new SubscriptionListenedToEvent($this->event, 6, $this->clock->now());

        $id = new Projector\Id();
        $projector = new Projector($id, $this::$postgres, $this->clock);

        $store = new InMemoryEventStore();
        $store->add($projector->id(), 0, $event1, $event2, $event3, $event4, $event5, $event6);

        $this->assertSame($event1, $projector->pick($store));
    }

    public function testError()
    {
        $event1 = new SubscriptionStarted($this->event, $this->clock->now());

        $id = new Projector\Id();
        $projector = new Projector($id, $this->connection, $this->clock);

        $this->connection
            ->expects($this->once())
            ->method('beginTransaction')
        ;

        $this->connection
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->statement)
        ;

        $exception = new \RuntimeException();

        $this->statement
            ->expects($this->once())
            ->method('execute')
            ->willThrowException($exception)
        ;

        $this->connection
            ->expects($this->once())
            ->method('rollBack')
        ;

        $this->expectExceptionObject($exception);

        $projector->on($event1);
    }
}

namespace Streak\Application\Listener\Subscriptions\ProjectorTest;

use Streak\Domain\Event\Listener;
use Streak\Domain\Id\UUID;

class Id extends UUID implements Listener\Id
{
}
