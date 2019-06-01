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

namespace Streak\Application\Command;

use PHPUnit\Framework\TestCase;
use Streak\Application\Command;
use Streak\Application\Command\HandlingTest\HandlingStub;
use Streak\Application\Command\HandlingTest\NotSupportedCommand1;
use Streak\Application\Command\HandlingTest\SupportedCommand1;
use Streak\Application\Command\HandlingTest\SupportedCommand2;
use Streak\Application\Command\HandlingTest\SupportedCommand3;
use Streak\Application\Command\HandlingTest\SupportedCommand4;
use Streak\Application\Exception\CommandNotSupported;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Application\Command\Handling
 */
class HandlingTest extends TestCase
{
    public function testSuccess()
    {
        $handler = new HandlingStub();

        $this->assertSame('query1', $handler->handle(new SupportedCommand1()));
        $this->assertSame(1, $handler->handle(new SupportedCommand2()));
        $this->assertSame(['query1', 1], $handler->handle(new SupportedCommand3()));
        $this->assertEquals(new \stdClass(), $handler->handle(new SupportedCommand4()));
    }

    /**
     * @dataProvider failingQueries
     */
    public function testFailure(Command $query)
    {
        $this->expectExceptionObject(new CommandNotSupported($query));

        $handler = new HandlingStub();
        $handler->handle($query);
    }

    public function failingQueries() : array
    {
        return [
            [new NotSupportedCommand1()],
        ];
    }
}

namespace Streak\Application\Command\HandlingTest;

use Streak\Application\Command;

class HandlingStub
{
    use Command\Handling;

    public function handle1(SupportedCommand1 $query)
    {
        return 'query1';
    }

    public function handle2(SupportedCommand2 $query)
    {
        return 1;
    }

    public function handle3(SupportedCommand3 $query)
    {
        return ['query1', 1];
    }

    public function handle4(SupportedCommand4 $query)
    {
        return new \stdClass();
    }

    public function notStartingWithHandle(NotSupportedCommand1 $query)
    {
        return 'notsupported';
    }

    public function handleHandlingMethodWithMoreThanOneParameter1(SupportedCommand1 $query1, SupportedCommand2 $query2)
    {
        return 'notsupported';
    }

    public function handleHandlingMethodWithMoreThanOneParameter2(SupportedCommand1 $query1, SupportedCommand2 $query2, NotSupportedCommand1 $query3)
    {
        return 'notsupported';
    }

    public function handleNotRequiredCommandParameter(?SupportedCommand1 $query1)
    {
        return 'notsupported';
    }

    public function handleNonCommandParameter(\stdClass $query1)
    {
        return 'notsupported';
    }

    private function handlePrivateMethodHandlingMethod(NotSupportedCommand1 $query)
    {
        return 'notsupported';
    }
}

class SupportedCommand1 implements Command
{
}

class SupportedCommand2 implements Command
{
}

class SupportedCommand3 implements Command
{
}

class SupportedCommand4 implements Command
{
}

class NotSupportedCommand1 implements Command
{
}
