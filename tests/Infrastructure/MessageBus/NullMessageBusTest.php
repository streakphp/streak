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
use Streak\Infrastructure\CommandBus\NullCommandBus;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\MessageBus\NullMessageBus
 */
class NullMessageBusTest extends TestCase
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

        $this->message1 = $this->getMockBuilder(Message::class)->setMockClassName('message1')->getMockForAbstractClass();
        $this->message2 = $this->getMockBuilder(Message::class)->setMockClassName('message2')->getMockForAbstractClass();
        $this->message3 = $this->getMockBuilder(Message::class)->setMockClassName('message3')->getMockForAbstractClass();
    }

    public function testBus()
    {
        $this->subscriber1
            ->expects($this->never())
            ->method('createFor')
        ;

        $this->subscriber2
            ->expects($this->never())
            ->method('createFor')
        ;

        $this->listener1
            ->expects($this->never())
            ->method('on')
        ;

        $bus = new NullMessageBus();
        $bus->subscribe($this->subscriber1);
        $bus->publish($this->message1, $this->message2);
        $bus->subscribe($this->subscriber2);
        $bus->publish($this->message3);
    }
}
