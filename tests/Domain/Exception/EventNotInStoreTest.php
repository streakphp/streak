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

namespace Streak\Domain\Exception;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Sensor\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Exception\EventNotInStore
 */
class EventNotInStoreTest extends TestCase
{
    /**
     * @var Event|MockObject
     */
    private $event;

    protected function setUp()
    {
        $this->event = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
    }

    public function testException()
    {
        $exception = new EventNotInStore($this->event);

        $this->assertSame('Event not in store.', $exception->getMessage());
        $this->assertSame($this->event, $exception->event());
    }
}
