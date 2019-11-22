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

namespace Streak\Infrastructure\Event\Subscription\DAO;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\Id\UUID;
use Streak\Infrastructure\Event\Converter\NestedObjectConverter;
use Streak\Infrastructure\Event\Subscription\DAO\DbalPostgresDAOTest\EventStub;
use Streak\Infrastructure\Event\Subscription\DAO\DbalPostgresDAOTest\ListenerId;
use Streak\Infrastructure\EventStore\InMemoryEventStore;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Event\Subscription\DAO\DbalPostgresDAO
 */
class DbalPostgresDAOTest extends TestCase
{
    /**
     * @var Event\Subscription\Factory|MockObject
     */
    private $subscriptions;

    /**
     * @var Event\Listener\Factory|MockObject
     */
    private $listeners;

    /**
     * @var Event\Listener|MockObject
     */
    private $listener1;

    /**
     * @var Event\Listener|MockObject
     */
    private $listener2;

    /**
     * @var Event\Envelope|MockObject
     */
    private $event;

    /**
     * @var DbalPostgresDAO
     */
    private $dao;

    /**
     * @var Connection
     */
    private static $connection;

    public static function setUpBeforeClass()
    {
        self::$connection = DriverManager::getConnection([
            'driver' => 'pdo_pgsql',
            'host' => $_ENV['PHPUNIT_POSTGRES_HOSTNAME'],
            'port' => (int) $_ENV['PHPUNIT_POSTGRES_PORT'],
            'dbname' => $_ENV['PHPUNIT_POSTGRES_DATABASE'],
            'user' => $_ENV['PHPUNIT_POSTGRES_USERNAME'],
            'password' => $_ENV['PHPUNIT_POSTGRES_PASSWORD'],
        ]);
    }

    protected function setUp()
    {
        $this->subscriptions = $this->getMockBuilder(Event\Subscription\Factory::class)->getMockForAbstractClass();
        $this->listeners = $this->getMockBuilder(Event\Listener\Factory::class)->getMockForAbstractClass();
        $this->listener1 = $this->getMockBuilder([Event\Listener::class, Event\Listener\Completable::class])->getMock();
        $this->listener2 = $this->getMockBuilder([Event\Listener::class, Event\Listener\Completable::class])->getMock();
        $this->event = new EventStub();
        $this->event = Event\Envelope::new($this->event, UUID::random());
        $this->dao = new DbalPostgresDAO(new Subscription\Factory(), $this->listeners, self::$connection, new NestedObjectConverter());
    }

    public function testDAO()
    {
        $this->dao->reset();

        $listenerId1 = ListenerId::fromString('275b4f3e-ff07-4a48-8a24-d895c8d257b9');
        $listenerId2 = ListenerId::fromString('2252d978-11b4-4f7f-ac97-d7228c031547');

        $event1 = new EventStub();
        $event1 = Event\Envelope::new($event1, UUID::random());
        $event2 = new EventStub();
        $event2 = Event\Envelope::new($event2, UUID::random());
        $event3 = new EventStub();
        $event3 = Event\Envelope::new($event3, UUID::random());

        $store = new InMemoryEventStore();
        $store->add($event1, $event2, $event3);

        $this->listener1
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($listenerId1)
        ;
        $this->listener2
            ->expects($this->atLeastOnce())
            ->method('listenerId')
            ->willReturn($listenerId2)
        ;

        $this->listener1
            ->expects($this->any())
            ->method('completed')
            ->willReturnOnConsecutiveCalls(false, true)
        ;

        $all = $this->dao->all();
        $all = iterator_to_array($all);

        $this->assertEmpty($all);

        $this->assertFalse($this->dao->exists($listenerId1));

        $subscription1 = new Subscription($this->listener1);
        $subscription1->startFor($event2);

        $this->dao->save($subscription1);

        $this->listeners
            ->expects($this->atLeastOnce())
            ->method('create')
            ->willReturnCallback(function (ListenerId $id) use ($listenerId1, $listenerId2) {
                if ($id->equals($listenerId1)) {
                    return $this->listener1;
                }
                if ($id->equals($listenerId2)) {
                    return $this->listener2;
                }
            })
        ;

        $all = $this->dao->all();
        $all = iterator_to_array($all);

        $this->assertEquals([$subscription1], $all, '');

        $this->assertTrue($this->dao->exists($listenerId1));

        $events = $subscription1->subscribeTo($store, 1);
        $events = iterator_to_array($events);

        $this->assertEquals([$event2], $events);

        $this->dao->save($subscription1);

        $all = $this->dao->all();
        $all = iterator_to_array($all);

        $this->assertEquals([$subscription1], $all, '');

        $this->assertTrue($this->dao->exists($listenerId1));

        $events = $subscription1->subscribeTo($store, 1);
        $events = iterator_to_array($events);

        $this->assertEquals([$event3], $events);

        $this->dao->save($subscription1);

        $all = $this->dao->all();
        $all = iterator_to_array($all);

        $this->assertEquals([$subscription1], $all, '');

        $this->assertTrue($this->dao->exists($listenerId1));

        $subscription2 = new Subscription($this->listener2);
        $subscription2->startFor($event2);

        $this->dao->save($subscription2);

        $all = $this->dao->all();
        $all = iterator_to_array($all);

        $this->assertEquals([$subscription1, $subscription2], $all, '');
    }
}

namespace Streak\Infrastructure\Event\Subscription\DAO\DbalPostgresDAOTest;

use Streak\Domain\Event;
use Streak\Domain\Id\UUID;

class EventStub implements Event
{
}

class ListenerId extends UUID implements Event\Listener\Id
{
}
