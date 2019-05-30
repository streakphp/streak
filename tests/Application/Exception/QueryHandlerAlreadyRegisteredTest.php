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

namespace Streak\Application\Exception;

use PHPUnit\Framework\TestCase;
use Streak\Application;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Application\Exception\QueryHandlerAlreadyRegistered
 */
class QueryHandlerAlreadyRegisteredTest extends TestCase
{
    private $handler;
    private $previous;

    protected function setUp()
    {
        $this->handler = $this->getMockBuilder(Application\QueryHandler::class)->getMockForAbstractClass();
        $this->previous = new \Exception();
    }

    public function testException()
    {
        $exception = new QueryHandlerAlreadyRegistered($this->handler, $this->previous);

        $this->assertSame($this->handler, $exception->handler());
        $this->assertSame($this->previous, $exception->getPrevious());
    }
}
