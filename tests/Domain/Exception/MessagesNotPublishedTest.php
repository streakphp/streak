<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain\Exception;

use PHPUnit\Framework\TestCase;
use Streak\Domain;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Exception\MessagesNotPublished
 */
class MessagesNotPublishedTest extends TestCase
{
    /**
     * @var Domain\Message|\PHPUnit_Framework_MockObject_MockObject
     */
    private $message1;

    /**
     * @var Domain\Message|\PHPUnit_Framework_MockObject_MockObject
     */
    private $message2;

    public function setUp()
    {
        $this->message1 = $this->getMockBuilder(Domain\Message::class)->getMockForAbstractClass();
        $this->message2 = $this->getMockBuilder(Domain\Message::class)->getMockForAbstractClass();
    }
    public function testException()
    {
        $messages = [$this->message1, $this->message2];

        $exception = new MessagesNotPublished(...$messages);

        $this->assertSame($messages, $exception->messages());
    }
}
