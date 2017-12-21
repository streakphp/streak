<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Infrastructure\MessageBus;

use PHPUnit\Framework\TestCase;
use Streak\Domain\Message;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\MessageBus\InMemoryMessageBus
 */
class InMemoryMessageBusTest extends TestCase
{
    /**
     * @var Message\Subscriber|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subscriber1;

    /**
     * @var Message\Subscriber|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subscriber2;

    /**
     * @var Message\Listener|\PHPUnit_Framework_MockObject_MockObject
     */
    private $listener1;

    /**
     * @var Message\Listener|\PHPUnit_Framework_MockObject_MockObject
     */
    private $listener2a;

    /**
     * @var Message\Listener|\PHPUnit_Framework_MockObject_MockObject
     */
    private $listener2b;

    /**
     * @var Message\Listener|\PHPUnit_Framework_MockObject_MockObject
     */
    private $listener3;

    /**
     * @var Message|\PHPUnit_Framework_MockObject_MockObject
     */
    private $message1;

    /**
     * @var Message|\PHPUnit_Framework_MockObject_MockObject
     */
    private $message2;

    /**
     * @var Message|\PHPUnit_Framework_MockObject_MockObject
     */
    private $message3;

    protected function setUp()
    {
        $this->subscriber1 = $this->getMockBuilder(Message\Subscriber::class)->setMockClassName('subscriber1')->getMockForAbstractClass();
        $this->subscriber2 = $this->getMockBuilder(Message\Subscriber::class)->setMockClassName('subscriber2')->getMockForAbstractClass();

        $this->listener1 = $this->getMockBuilder(Message\Listener::class)->setMockClassName('listener1')->getMockForAbstractClass();
        $this->listener2a = $this->getMockBuilder(Message\Listener::class)->setMockClassName('listener2a')->getMockForAbstractClass();
        $this->listener2b = $this->getMockBuilder(Message\Listener::class)->setMockClassName('listener2b')->getMockForAbstractClass();
        $this->listener3 = $this->getMockBuilder(Message\Listener::class)->setMockClassName('listener3')->getMockForAbstractClass();

        $this->message1 = $this->getMockBuilder(Message::class)->setMockClassName('message1')->getMockForAbstractClass();
        $this->message2 = $this->getMockBuilder(Message::class)->setMockClassName('message2')->getMockForAbstractClass();
        $this->message3 = $this->getMockBuilder(Message::class)->setMockClassName('message3')->getMockForAbstractClass();
    }

    public function testBus()
    {
        $bus = new InMemoryMessageBus();

        $bus->subscribe($this->subscriber1);
        $bus->subscribe($this->subscriber2);
        $bus->listen($this->listener3);

        $this->subscriber1
            ->expects($this->at(0))
            ->method('createFor')
            ->with($this->message1)
            ->willReturn($this->listener1)
        ;

        $this->subscriber1
            ->expects($this->at(1))
            ->method('createFor')
            ->with($this->message2)
            ->willThrowException(new Message\Exception\InvalidMessageGiven($this->message2))
        ;

        $this->subscriber1
            ->expects($this->at(2))
            ->method('createFor')
            ->with($this->message3)
            ->willThrowException(new Message\Exception\InvalidMessageGiven($this->message2))
        ;

        $this->subscriber2
            ->expects($this->at(0))
            ->method('createFor')
            ->with($this->message1)
            ->willThrowException(new Message\Exception\InvalidMessageGiven($this->message1))
        ;

        $this->subscriber2
            ->expects($this->at(1))
            ->method('createFor')
            ->with($this->message2)
            ->willReturn($this->listener2a);
        ;

        $this->subscriber2
            ->expects($this->at(2))
            ->method('createFor')
            ->with($this->message3)
            ->willReturn($this->listener2b);
        ;

        $this->listener1
            ->expects($this->at(0))
            ->method('on')
            ->with($this->message1)
        ;

        $this->listener1
            ->expects($this->at(1))
            ->method('on')
            ->with($this->message2)
        ;

        $this->listener1
            ->expects($this->at(2))
            ->method('on')
            ->with($this->message3)
        ;

        $this->listener2a
            ->expects($this->at(0))
            ->method('on')
            ->with($this->message2)
        ;

        $this->listener2a
            ->expects($this->at(1))
            ->method('on')
            ->with($this->message3)
        ;

        $this->listener2b
            ->expects($this->at(0))
            ->method('on')
            ->with($this->message3)
        ;

        $this->listener3
            ->expects($this->at(0))
            ->method('on')
            ->with($this->message1)
        ;

        $this->listener3
            ->expects($this->at(1))
            ->method('on')
            ->with($this->message2)
        ;

        $this->listener3
            ->expects($this->at(2))
            ->method('on')
            ->with($this->message3)
        ;

        $bus->publish($this->message1, $this->message2);
        $bus->publish($this->message3);
    }
}
