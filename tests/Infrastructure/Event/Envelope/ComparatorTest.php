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

namespace Streak\Infrastructure\Event\Envelope;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Comparator\Factory;
use Streak\Domain\Event;
use Streak\Domain\Id\UUID;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Event\Envelope\Comparator
 */
class ComparatorTest extends TestCase
{
    /**
     * @var Factory|MockObject
     */
    private $factory;

    /**
     * @var Comparator
     */
    private $comparator;

    /**
     * @var \SebastianBergmann\Comparator\Comparator
     */
    private $subcomparator;

    /**
     * @var UUID
     */
    private $uuid;

    /**
     * @var Event|MockObject
     */
    private $event1;

    /**
     * @var Event\Envelope|MockObject
     */
    private $envelope1a;

    /**
     * @var Event\Envelope|MockObject
     */
    private $envelope1b;

    /**
     * @var Event|MockObject
     */
    private $event2;

    /**
     * @var Event\Envelope|MockObject
     */
    private $envelope2;

    protected function setUp() : void
    {
        $this->factory = $this->getMockBuilder(Factory::class)->disableOriginalConstructor()->getMock();
        $this->comparator = new Comparator();
        $this->comparator->setFactory($this->factory);
        $this->subcomparator = $this->getMockBuilder(\SebastianBergmann\Comparator\Comparator::class)->disableOriginalConstructor()->getMock();

        $this->uuid = UUID::random();
        $this->event1 = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $this->envelope1a = new Event\Envelope($this->uuid, 'name', $this->event1, UUID::random());
        $this->envelope1b = new Event\Envelope($this->uuid, 'name', $this->event1, UUID::random());
        $this->event2 = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $this->envelope2 = Event\Envelope::new($this->event2, UUID::random());
    }

    public function notAcceptable()
    {
        return [
            [1, 1],
            [PHP_INT_MIN, PHP_INT_MAX],
            [1.1, 1.0],
            [-1.1, 1.0],
            [PHP_FLOAT_MIN, PHP_FLOAT_MAX],
            [[], []],
            [[1], [1]],
            [new \stdClass(), new \stdClass()],
        ];
    }

    public function acceptable()
    {
        $event1 = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $envelope1 = Event\Envelope::new($event1, UUID::random());
        $event2 = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $envelope2 = Event\Envelope::new($event2, UUID::random());

        return [
            [$envelope1, $envelope2],
            [$envelope1, $event1],
            [$event1, $envelope1],
        ];
    }

    /**
     * @dataProvider notAcceptable
     */
    public function testNotAccepting($expected, $actual)
    {
        $this->factory
            ->expects($this->never())
            ->method($this->anything())
        ;

        $this->assertFalse($this->comparator->accepts($expected, $actual));
    }

    /**
     * @dataProvider acceptable
     */
    public function testAccepting($expected, $actual)
    {
        $this->factory
            ->expects($this->never())
            ->method($this->anything())
        ;

        $this->assertTrue($this->comparator->accepts($expected, $actual));
    }

    public function testEqualEnvelopes()
    {
        $this->assertNull($this->comparator->assertEquals($this->envelope1a, $this->envelope1b));
    }

    public function testNotEqualEnvelopes()
    {
        $this->expectException(ComparisonFailure::class);

        $this->comparator->assertEquals($this->envelope1a, $this->envelope2);
    }

    public function testNotEqual1()
    {
        $this->factory
            ->expects($this->once())
            ->method('getComparatorFor')
            ->with($this->event1, $this->event2)
            ->willReturn($this->subcomparator)
        ;

        $this->subcomparator
            ->expects($this->once())
            ->method('assertEquals')
            ->with($this->event1, $this->event2)
        ;

        $this->comparator->assertEquals($this->envelope1a, $this->event2);
    }

    public function testNotEqual2()
    {
        $this->factory
            ->expects($this->once())
            ->method('getComparatorFor')
            ->with($this->event1, $this->event2)
            ->willReturn($this->subcomparator)
        ;

        $this->subcomparator
            ->expects($this->once())
            ->method('assertEquals')
            ->with($this->event1, $this->event2)
        ;

        $this->comparator->assertEquals($this->event1, $this->envelope2);
    }
}
