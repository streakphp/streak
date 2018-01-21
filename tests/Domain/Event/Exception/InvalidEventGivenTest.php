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

namespace Streak\Domain\Event\Exception;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Sensor\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Exception\InvalidEventGiven
 */
class InvalidEventGivenTest extends TestCase
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
        $exception = new InvalidEventGiven($this->event);

        $this->assertSame('Invalid event given.', $exception->getMessage());
        $this->assertSame($this->event, $exception->event());
    }
}
