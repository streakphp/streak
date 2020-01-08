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

use PHPUnit\Framework\TestCase;
use Streak\Domain;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Exception\SourcingObjectWithEventFailed
 */
class SourcingObjectWithEventFailedTest extends TestCase
{
    /**
     * @var Event\Sourced\Aggregate|\PHPUnit_Framework_MockObject_MockObject
     */
    private $aggregate;

    /**
     * @var Domain\Event\Envelope|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event;

    public function setUp()
    {
        $this->aggregate = $this->getMockBuilder(Event\Sourced\Aggregate::class)->getMockForAbstractClass();
        $event = $this->getMockBuilder(Domain\Event::class)->getMockForAbstractClass();
        $this->event = Event\Envelope::new($event, Domain\Id\UUID::random());
    }

    public function testException()
    {
        $exception = new SourcingObjectWithEventFailed($this->aggregate, $this->event);

        $this->assertSame($this->aggregate, $exception->subject());
        $this->assertSame($this->event, $exception->event());
    }

    /**
     * Remove when typing by "object" will be possible (PHP 7.2).
     */
    public function testNonObjectSubject()
    {
        $expected = new \InvalidArgumentException('Object expected, but got "string"');
        $this->expectExceptionObject($expected);

        new SourcingObjectWithEventFailed('not an object', $this->event);
    }
}
