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

namespace Streak\Infrastructure\Domain\Event\Envelope;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Comparator\Factory;
use Streak\Domain\Event;
use Streak\Domain\Id\UUID;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\Event\Envelope\Comparator
 */
class ComparatorTest extends TestCase
{
    private Factory|MockObject $factory;

    private Comparator $comparator;
    private Comparator|MockObject $subcomparator;

    private UUID $uuid;

    private Event|MockObject $event1;
    private Event|MockObject $event2;

    private Event\Envelope $envelope1a;
    private Event\Envelope $envelope1b;
    private Event\Envelope $envelope2;

    protected function setUp(): void
    {
        $this->factory = $this->getMockBuilder(Factory::class)->disableOriginalConstructor()->getMock();
        $this->comparator = new Comparator();
        $this->comparator->setFactory($this->factory);
        $this->subcomparator = $this->getMockBuilder(Comparator::class)->disableOriginalConstructor()->getMock();

        $this->uuid = UUID::random();
        $this->event1 = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $this->envelope1a = new Event\Envelope($this->uuid, 'name', $this->event1, UUID::random(), UUID::random());
        $this->envelope1b = new Event\Envelope($this->uuid, 'name', $this->event1, UUID::random(), UUID::random());
        $this->event2 = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $this->envelope2 = Event\Envelope::new($this->event2, UUID::random());
    }

    public function notAcceptable()
    {
        return [
            [1, 1],
            [\PHP_INT_MIN, \PHP_INT_MAX],
            [1.1, 1.0],
            [-1.1, 1.0],
            [2.2250738585072e-308, 1.7976931348623e+308],
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
     *
     * @param mixed $expected
     * @param mixed $actual
     */
    public function testNotAccepting($expected, $actual): void
    {
        $this->factory
            ->expects(self::never())
            ->method(self::anything())
        ;

        self::assertFalse($this->comparator->accepts($expected, $actual));
    }

    /**
     * @dataProvider acceptable
     *
     * @param mixed $expected
     * @param mixed $actual
     */
    public function testAccepting($expected, $actual): void
    {
        $this->factory
            ->expects(self::never())
            ->method(self::anything())
        ;

        self::assertTrue($this->comparator->accepts($expected, $actual));
    }

    public function testEqualEnvelopes(): void
    {
//        $this->expectNotToPerformAssertions();

        try {
            $this->comparator->assertEquals($this->envelope1a, $this->envelope1b);
        } catch (ComparisonFailure) {
            self::fail();
        }

        $this->addToAssertionCount(1); // tests without assertions does not report any coverage, so this is a hack @link https://github.com/sebastianbergmann/phpunit/pull/3348
    }

    public function testNotEqualEnvelopes(): void
    {
        $this->expectException(ComparisonFailure::class);

        $this->comparator->assertEquals($this->envelope1a, $this->envelope2);
    }

    public function testNotEqual1(): void
    {
        $this->factory
            ->expects(self::once())
            ->method('getComparatorFor')
            ->with($this->event1, $this->event2)
            ->willReturn($this->subcomparator)
        ;

        $this->subcomparator
            ->expects(self::once())
            ->method('assertEquals')
            ->with($this->event1, $this->event2)
        ;

        $this->comparator->assertEquals($this->envelope1a, $this->event2);
    }

    public function testNotEqual2(): void
    {
        $this->factory
            ->expects(self::once())
            ->method('getComparatorFor')
            ->with($this->event1, $this->event2)
            ->willReturn($this->subcomparator)
        ;

        $this->subcomparator
            ->expects(self::once())
            ->method('assertEquals')
            ->with($this->event1, $this->event2)
        ;

        $this->comparator->assertEquals($this->event1, $this->envelope2);
    }
}
