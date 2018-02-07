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

namespace Streak\Infrastructure;

use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\EventStore;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\UnitOfWork
 */
class UnitOfWorkTest extends TestCase
{
    /**
     * @var EventStore|\PHPUnit_Framework_MockObject_MockObject
     */
    private $store;

    /**
     * @var Event\Sourced|\PHPUnit_Framework_MockObject_MockObject
     */
    private $object1;

    /**
     * @var Event\Sourced|\PHPUnit_Framework_MockObject_MockObject
     */
    private $object2;

    /**
     * @var Event\Sourced|\PHPUnit_Framework_MockObject_MockObject
     */
    private $object3;

    /**
     * @var Event\Producer\Id|\PHPUnit_Framework_MockObject_MockObject
     */
    private $id1;

    /**
     * @var Event\Producer\Id|\PHPUnit_Framework_MockObject_MockObject
     */
    private $id3;

    /**
     * @var Event\Sourced|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event1;

    /**
     * @var Event\Sourced|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event2;

    /**
     * @var Event\Sourced|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event3;

    /**
     * @var Event\Sourced|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event4;

    public function setUp()
    {
        $this->store = $this->getMockBuilder(EventStore::class)->getMockForAbstractClass();

        $this->object1 = $this->getMockBuilder(Event\Sourced::class)->setMockClassName('object1')->getMockForAbstractClass();
        $this->object2 = $this->getMockBuilder(Event\Sourced::class)->setMockClassName('object2')->getMockForAbstractClass();
        $this->object3 = $this->getMockBuilder(Event\Sourced::class)->setMockClassName('object3')->getMockForAbstractClass();

        $this->id1 = $this->getMockBuilder(Event\Producer\Id::class)->setMockClassName('id1')->getMockForAbstractClass();
        $this->id3 = $this->getMockBuilder(Event\Producer\Id::class)->setMockClassName('id3')->getMockForAbstractClass();

        $this->event1 = $this->getMockBuilder(Event::class)->setMockClassName('event1')->getMockForAbstractClass();
        $this->event2 = $this->getMockBuilder(Event::class)->setMockClassName('event2')->getMockForAbstractClass();
        $this->event3 = $this->getMockBuilder(Event::class)->setMockClassName('event3')->getMockForAbstractClass();
        $this->event4 = $this->getMockBuilder(Event::class)->setMockClassName('event4')->getMockForAbstractClass();
    }

    public function testObject()
    {
        $this->object1
            ->expects($this->once())
            ->method('last')
            ->with()
            ->willReturn(null)
        ;

        $this->object1
            ->expects($this->once())
            ->method('events')
            ->with()
            ->willReturn([$this->event1])
        ;

        $this->object1
            ->expects($this->once())
            ->method('producerId')
            ->with()
            ->willReturn($this->id1)
        ;

        $this->object2
            ->expects($this->once())
            ->method('last')
            ->with()
            ->willReturn(null)
        ;

        $this->object2
            ->expects($this->never())
            ->method('producerId')
        ;

        $this->object2
            ->expects($this->never())
            ->method('events')
        ;

        $this->object3
            ->expects($this->once())
            ->method('last')
            ->with()
            ->willReturn($this->event2)
        ;

        $this->object3
            ->expects($this->once())
            ->method('producerId')
            ->with()
            ->willReturn($this->id3)
        ;

        $this->object3
            ->expects($this->once())
            ->method('events')
            ->with()
            ->willReturn([$this->event3, $this->event4])
        ;

        $uow = new UnitOfWork($this->store);

        $this->assertEquals(0, $uow->count());
        $this->assertFalse($uow->has($this->object1));
        $this->assertFalse($uow->has($this->object2));
        $this->assertFalse($uow->has($this->object3));

        $uow->add($this->object1);

        $this->assertEquals(1, $uow->count());
        $this->assertTrue($uow->has($this->object1));
        $this->assertFalse($uow->has($this->object2));
        $this->assertFalse($uow->has($this->object3));

        $uow->add($this->object2);

        $this->assertEquals(2, $uow->count());
        $this->assertTrue($uow->has($this->object1));
        $this->assertTrue($uow->has($this->object2));
        $this->assertFalse($uow->has($this->object3));

        $uow->remove($this->object2);

        $this->assertEquals(1, $uow->count());
        $this->assertTrue($uow->has($this->object1));
        $this->assertFalse($uow->has($this->object2));
        $this->assertFalse($uow->has($this->object3));

        $uow->add($this->object3);

        $this->assertEquals(2, $uow->count());
        $this->assertTrue($uow->has($this->object1));
        $this->assertFalse($uow->has($this->object2));
        $this->assertTrue($uow->has($this->object3));

        $this->store
            ->expects($this->at(0))
            ->method('add')
            ->with($this->id1, null, $this->event1)
        ;

        $this->store
            ->expects($this->at(1))
            ->method('add')
            ->with($this->id3, $this->event2, $this->event3, $this->event4)
        ;

        $uow->commit();

        $this->assertEquals(0, $uow->count());
        $this->assertFalse($uow->has($this->object1));
        $this->assertFalse($uow->has($this->object2));
        $this->assertFalse($uow->has($this->object3));
    }
}
