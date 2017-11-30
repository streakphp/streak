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
 * @covers \Streak\Domain\Exception\MessageNotPublished
 */
class MessageNotPublishedTest extends TestCase
{
    /**
     * @var Domain\Message|\PHPUnit_Framework_MockObject_MockObject
     */
    private $message;

    public function setUp()
    {
        $this->message = $this->getMockBuilder(Domain\Message::class)->getMockForAbstractClass();
    }
    public function testException()
    {
        $exception = new MessageNotPublished($this->message);

        $this->assertSame($this->message, $exception->message());
    }
}
