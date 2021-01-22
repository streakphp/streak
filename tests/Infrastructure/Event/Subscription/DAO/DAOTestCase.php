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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Clock;
use Streak\Domain\Event;
use Streak\Domain\Id\UUID;
use Streak\Infrastructure\Event\Subscription\DAO;
use Streak\Infrastructure\Event\Subscription\DAO\DAOTestCase\EventStub;
use Streak\Infrastructure\Event\Subscription\DAO\DAOTestCase\ListenerId;
use Streak\Infrastructure\EventStore\InMemoryEventStore;
use Streak\Infrastructure\FixedClock;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
abstract class DAOTestCase extends TestCase
{
    /**
     * @var DAO
     */
    protected $dao;

    /**
     * @var Event\Subscription\Factory|MockObject
     */
    protected $subscriptions;

    /**
     * @var Event\Listener\Factory|MockObject
     */
    protected $listeners;

    /**
     * @var Event\Listener|MockObject
     */
    protected $listener1;

    /**
     * @var Event\Listener|MockObject
     */
    protected $listener2;

    /**
     * @var Event\Envelope|MockObject
     */
    protected $event;

    /**
     * @var Clock
     */
    protected $clock;

    protected function setUp()
    {
        $this->subscriptions = $this->getMockBuilder(Event\Subscription\Factory::class)->getMockForAbstractClass();
        $this->listeners = $this->getMockBuilder(Event\Listener\Factory::class)->getMockForAbstractClass();
        $this->listener1 = $this->getMockBuilder([Event\Listener::class, Event\Listener\Completable::class])->setMockClassName('listener1')->getMock();
        $this->listener2 = $this->getMockBuilder([Event\Listener::class, Event\Listener\Completable::class])->setMockClassName('listener2')->getMock();
        $this->event = new EventStub();
        $this->event = Event\Envelope::new($this->event, UUID::random());
        $this->clock = new FixedClock(new \DateTime('2018-09-28 19:12:32.763188 +00:00'));
        $this->dao = $this->newDAO(new Subscription\Factory($this->clock), $this->listeners);
    }

    public function testDAO()
    {
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
        $this->listener2
            ->expects($this->any())
            ->method('completed')
            ->willReturnOnConsecutiveCalls(false, false)
        ;

        $all = $this->dao->all();
        $all = iterator_to_array($all);

        $this->assertEmpty($all);

        $this->assertFalse($this->dao->exists($listenerId1));
        $this->assertNull($this->dao->one($listenerId1));

        $subscription1 = new Subscription($this->listener1, $this->clock);
        $subscription1->startFor($event2);

        $this->dao->save($subscription1);

        $this->listeners
            ->expects($this->any())
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
        $this->assertEquals($subscription1, $this->dao->one($listenerId1));

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
        $this->assertFalse($this->dao->exists($listenerId2));
        $this->assertNull($this->dao->one($listenerId2));

        $subscription2 = new Subscription($this->listener2, $this->clock);
        $subscription2->startFor($event2);

        $this->assertFalse($this->dao->exists($listenerId2));
        $this->assertNull($this->dao->one($listenerId2));

        $this->dao->save($subscription2);

        $this->assertTrue($this->dao->exists($listenerId2));
        $this->assertNotNull($this->dao->one($listenerId2));

        $all = $this->dao->all();
        $all = iterator_to_array($all);

        $this->assertEquals([$subscription1, $subscription2], $all, '');

        $all = $this->dao->all([ListenerId::class]);
        $all = iterator_to_array($all);

        $this->assertEquals([$subscription1, $subscription2], $all, '');

        $all = $this->dao->all([\stdClass::class, ListenerId::class]);
        $all = iterator_to_array($all);

        $this->assertEquals([$subscription1, $subscription2], $all, '');

        $all = $this->dao->all([\stdClass::class]);
        $all = iterator_to_array($all);

        $this->assertEquals([], $all, '');

        $all = $this->dao->all([], true);
        $all = iterator_to_array($all);

        $this->assertEquals([$subscription1], $all, '');

        $all = $this->dao->all([], false);
        $all = iterator_to_array($all);

        $this->assertEquals([$subscription2], $all, '');

        $subscription2->pause();

        $this->assertTrue($subscription2->paused());

        $this->dao->save($subscription2);

        $subscription2b = $this->dao->one($listenerId2);
        $this->assertTrue($subscription2b->paused());

        $subscription2->unpause();

        $this->assertFalse($subscription2->paused());

        $this->dao->save($subscription2);

        $subscription2b = $this->dao->one($listenerId2);
        $this->assertFalse($subscription2b->paused());
    }

    abstract public function newDAO(Subscription\Factory $subscriptions, Event\Listener\Factory $listeners) : DAO;
}

namespace Streak\Infrastructure\Event\Subscription\DAO\DAOTestCase;

use Streak\Domain\Event;
use Streak\Domain\Id\UUID;

class EventStub implements Event
{
}

class ListenerId extends UUID implements Event\Listener\Id
{
}
