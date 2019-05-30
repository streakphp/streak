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

namespace Streak\Domain\Event\Subscription\Exception;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Subscription\Exception\ListenerNotFound
 */
class ListenerNotFoundTest extends TestCase
{
    /**
     * @var Event\Listener\Id|MockObject
     */
    private $listenerId;

    public function setUp()
    {
        $this->listenerId = $this->getMockBuilder(Event\Listener\Id::class)->getMockForAbstractClass();
    }

    public function testException()
    {
        $exception = new ListenerNotFound($this->listenerId);

        $this->assertSame($this->listenerId, $exception->listenerId());
    }
}
