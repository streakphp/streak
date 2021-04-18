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
    private ?\Exception $previous = null;

    protected function setUp(): void
    {
        $this->handler = $this->getMockBuilder(Application\QueryHandler::class)->getMockForAbstractClass();
        $this->previous = new \Exception();
    }

    public function testException(): void
    {
        $exception = new QueryHandlerAlreadyRegistered($this->handler, $this->previous);

        self::assertSame($this->handler, $exception->handler());
        self::assertSame($this->previous, $exception->getPrevious());
    }
}
