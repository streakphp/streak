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
use Streak\Application\Saga\Exception\InvalidFirstMessageGiven;
use Streak\Application\Saga\MessagingTest\ListenedMessage1;
use Streak\Application\Saga\MessagingTest\ListenedMessage2;
use Streak\Application\Saga\MessagingTest\NotListenedMessage;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Application\Saga\Messaging
 */
class MessagingTest extends TestCase
{
    private $bus;

    protected function setUp()
    {
        $this->bus = $this->getMockBuilder(CommandBus::class)->getMockForAbstractClass();
    }

    public function testListening()
    {
        $messaging = new MessagingTest\MessagingStub();
        $messaging->on(new ListenedMessage1(), $this->bus);

        $this->assertTrue($messaging->isMessage1Listened());

        $messaging->on(new NotListenedMessage(), $this->bus);
    }

    public function testReplayingInOrder()
    {
        $messaging = new MessagingTest\MessagingStub();
        $messaging->on(new ListenedMessage1(), $this->bus);

        $messaging->replay();

        $messaging = new MessagingTest\MessagingStub();
        $messaging->on(new ListenedMessage1(), $this->bus);

        $messaging->replay(new ListenedMessage1());

        $this->assertTrue($messaging->isMessage1Listened());
        $this->assertFalse($messaging->isMessage2Listened());

        $messaging = new MessagingTest\MessagingStub();
        $messaging->on(new ListenedMessage1(), $this->bus);

        $messaging->replay(new ListenedMessage1(), new ListenedMessage2());

        $this->assertTrue($messaging->isMessage1Listened());
        $this->assertTrue($messaging->isMessage2Listened());
    }

    public function testReplayingOutOfOrder()
    {
        $messaging = new MessagingTest\MessagingStub();
        $messaging->on(new ListenedMessage1(), $this->bus);

        $message2 = new ListenedMessage2();
        $message1 = new ListenedMessage1();

        $exception = new InvalidFirstMessageGiven($message2);
        $this->expectExceptionObject($exception);

        $messaging->replay($message2, $message1);

    }
}

namespace Streak\Application\Saga\MessagingTest;

use Streak\Application;
use Streak\Application\Saga;
use Streak\Domain;

class MessagingStub
{
    use Saga\Messaging;

    private $message1Listened = false;
    private $message2Listened = false;

    public function beginsWith(Domain\Message $message) : bool
    {
        return $message instanceof ListenedMessage1;
    }

    public function onMessage1(ListenedMessage1 $message, Application\CommandBus $bus) : void
    {
        $this->message1Listened = true;
    }

    public function onMessage2(ListenedMessage2 $message, Application\CommandBus $bus) : void
    {
        $this->message2Listened = true;
    }

    public function onNotListenedMessageWithOptionalMessage(NotListenedMessage $message2 = null, Application\CommandBus $bus)
    {
    }

    public function onNotListenedMessageWithOptionalBus(NotListenedMessage $message2, Application\CommandBus $bus = null)
    {
    }

    public function onNonMessage(\stdClass $notMessage, Application\CommandBus $bus)
    {
    }

    public function onNonCommandBus(NotListenedMessage $message2, \stdClass $notCommandBus)
    {
    }

    protected function onNotListenedMessageButProtected(NotListenedMessage $message2 = null, Application\CommandBus $bus)
    {
    }

    private function onNotListenedMessageButPrivate(NotListenedMessage $message2 = null, Application\CommandBus $bus)
    {
    }

    public function onNotListenedMessageWithMoreThanTwoParameters(NotListenedMessage $message2 = null, Application\CommandBus $bus, $notNeeded)
    {
    }

    public function isMessage1Listened() : bool
    {
        return $this->message1Listened;
    }

    public function isMessage2Listened() : bool
    {
        return $this->message2Listened;
    }
}

class ListenedMessage1 implements Domain\Message
{
}

class ListenedMessage2 implements Domain\Message
{
}

class NotListenedMessage implements Domain\Message
{
}
