<?php

declare(strict_types=1);

/**
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain\Message\Exception;

use PHPUnit\Framework\TestCase;
use Streak\Domain\Message;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Message\Exception\InvalidMessageGiven
 */
class InvalidMessageGivenTest extends TestCase
{
    public function testException()
    {
        $message = $this->getMockBuilder(Message::class)->getMockForAbstractClass();

        $exception = new InvalidMessageGiven($message);

        $this->assertSame('Invalid message given.', $exception->getMessage());
        $this->assertSame($message, $exception->givenMessage());
    }
}
