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

use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Subscription\Exception\ListenerNotFound
 */
class ListenerNotFoundTest extends TestCase
{
    private \Streak\Application\Event\Listener\Id $listenerId;

    protected function setUp(): void
    {
        $this->listenerId = $this->getMockBuilder(\Streak\Application\Event\Listener\Id::class)->setMockClassName('listener_id_1')->getMockForAbstractClass();
    }

    public function testException(): void
    {
        $this->listenerId
            ->expects(self::once())
            ->method('toString')
            ->willReturn('8db25a31-45ce-499f-95f7-8b8d4fffc366')
        ;

        $exception = new ListenerNotFound($this->listenerId);

        self::assertSame('Listener "listener_id_1@8db25a31-45ce-499f-95f7-8b8d4fffc366" not found.', $exception->getMessage());
        self::assertSame($this->listenerId, $exception->listenerId());
    }
}
