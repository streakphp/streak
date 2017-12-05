<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain\Message;

use PHPUnit\Framework\TestCase;
use Streak\Domain\Message\ListeningTest\ListenerStub;
use Streak\Domain\Message\ListeningTest\Message1;
use Streak\Domain\Message\ListeningTest\Message2;
use Streak\Domain\Message\ListeningTest\Message3;
use Streak\Domain\Message\ListeningTest\Message4;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Message\Listening
 */
class ListeningTest extends TestCase
{
    public function testListening()
    {
        $listener = new ListenerStub();

        $this->assertFalse($listener->isMessage1Listened());
        $this->assertFalse($listener->isMessage2Listened());
        $this->assertFalse($listener->isMessage3Listened());
        $this->assertFalse($listener->isMessage4Listened());

        $listener->onMessage(new Message1());

        $this->assertTrue($listener->isMessage1Listened());
        $this->assertFalse($listener->isMessage2Listened());
        $this->assertFalse($listener->isMessage3Listened());
        $this->assertFalse($listener->isMessage4Listened());

        $listener->onMessage(new Message2());

        $this->assertTrue($listener->isMessage1Listened());
        $this->assertFalse($listener->isMessage2Listened());
        $this->assertFalse($listener->isMessage3Listened());
        $this->assertFalse($listener->isMessage4Listened());

        $listener->onMessage(new Message3());

        $this->assertTrue($listener->isMessage1Listened());
        $this->assertFalse($listener->isMessage2Listened());
        $this->assertFalse($listener->isMessage3Listened());
        $this->assertFalse($listener->isMessage4Listened());

        $listener->onMessage(new Message4());

        $this->assertTrue($listener->isMessage1Listened());
        $this->assertFalse($listener->isMessage2Listened());
        $this->assertFalse($listener->isMessage3Listened());
        $this->assertFalse($listener->isMessage4Listened());
    }
}

namespace Streak\Domain\Message\ListeningTest;

use Streak\Domain\Message;

class ListenerStub
{
    use Message\Listening;

    private $message1Listened = false;
    private $message2Listened = false;
    private $message3Listened = false;
    private $message4Listened = false;

    public function onMessage1(Message1 $message1)
    {
        $this->message1Listened = true;
    }

    public function onMessage1WithOptionalMessage(Message2 $message2 = null)
    {
    }

    public function onMessage1WithAdditionalUnnecessaryParameter(Message2 $message2, $unnecessary)
    {
    }

    public function onNonMessage(\stdClass $parameter)
    {
    }

    protected function onMessage3(Message3 $message3)
    {
        $this->message3Listened = true;
    }

    protected function onMessage4(Message4 $message4)
    {
        $this->message4Listened = true;
    }

    public function isMessage1Listened() : bool
    {
        return $this->message1Listened;
    }

    public function isMessage2Listened() : bool
    {
        return $this->message2Listened;
    }

    public function isMessage3Listened() : bool
    {
        return $this->message3Listened;
    }

    public function isMessage4Listened() : bool
    {
        return $this->message4Listened;
    }
}

class Message1 implements Message
{
}

class Message2 implements Message
{
}

class Message3 implements Message
{
}

class Message4 implements Message
{
}
