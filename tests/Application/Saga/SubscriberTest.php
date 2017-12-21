<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Application\Saga;

use PHPUnit\Framework\TestCase;
use Streak\Application\CommandBus;
use Streak\Application\Saga;
use Streak\Domain\Message;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Application\Saga\Subscriber
 */
class SubscriberTest extends TestCase
{
    /**
     * @var Saga\Factory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $factory;

    /**
     * @var CommandBus|\PHPUnit_Framework_MockObject_MockObject
     */
    private $bus;

    /**
     * @var Saga|\PHPUnit_Framework_MockObject_MockObject
     */
    private $saga;

    /**
     * @var Message|\PHPUnit_Framework_MockObject_MockObject
     */
    private $message1;

    /**
     * @var Message|\PHPUnit_Framework_MockObject_MockObject
     */
    private $message2;

    protected function setUp()
    {
        $this->factory = $this->getMockBuilder(Saga\Factory::class)->getMockForAbstractClass();
        $this->bus = $this->getMockBuilder(CommandBus::class)->getMockForAbstractClass();
        $this->saga = $this->getMockBuilder(Saga::class)->getMockForAbstractClass();
        $this->message1 = $this->getMockBuilder(Message::class)->getMockForAbstractClass();
        $this->message2 = $this->getMockBuilder(Message::class)->getMockForAbstractClass();
    }

    public function testSubscriber()
    {
        $this->factory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->saga)
        ;

        $this->saga
            ->expects($this->exactly(2))
            ->method('beginsWith')
            ->withConsecutive(
                [$this->message1],
                [$this->message2]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false
            )
        ;

        $listener = new Listener($this->factory, $this->bus);
        $subscriber = new Subscriber($listener);

        $this->assertSame($listener, $subscriber->createFor($this->message1));

        $exception = new Message\Exception\InvalidMessageGiven($this->message2);
        $this->expectExceptionObject($exception);

        $subscriber->createFor($this->message2);
    }
}
