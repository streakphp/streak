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

namespace Streak\Infrastructure\Domain\Event\Converter;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\Event\Converter\CompositeConverter
 */
class CompositeConverterTest extends TestCase
{
    private Event\Converter|MockObject $converter1;
    private Event\Converter|MockObject $converter2;
    private Event\Converter|MockObject $converter3;

    private Event|MockObject $message;

    protected function setUp(): void
    {
        $this->converter1 = $this->getMockBuilder(Event\Converter::class)->getMockForAbstractClass();
        $this->converter2 = $this->getMockBuilder(Event\Converter::class)->getMockForAbstractClass();
        $this->converter3 = $this->getMockBuilder(Event\Converter::class)->getMockForAbstractClass();

        $this->message = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
    }

    public function testConvertingToArray(): void
    {
        $data = ['test' => 'data'];

        $this->converter1
            ->expects(self::once())
            ->method('objectToArray')
            ->with($this->message)
            ->willThrowException(new Event\Exception\ConversionToArrayNotPossible($this->message))
        ;

        $this->converter2
            ->expects(self::once())
            ->method('objectToArray')
            ->with($this->message)
            ->willReturn($data)
        ;

        $this->converter3
            ->expects(self::never())
            ->method('objectToArray')
        ;

        $composite = new CompositeConverter();
        $composite->addConverter($this->converter1);
        $composite->addConverter($this->converter2);
        $composite->addConverter($this->converter3);

        $result = $composite->objectToArray($this->message);

        self::assertSame($data, $result);
    }

    public function testConvertingToArrayWithUnexpectedException(): void
    {
        $unexpectedException = new \InvalidArgumentException('Unexpected Exception.');
        $expectedException = new Event\Exception\ConversionToArrayNotPossible($this->message, $unexpectedException);

        $this->converter1
            ->expects(self::once())
            ->method('objectToArray')
            ->with($this->message)
            ->willThrowException(new Event\Exception\ConversionToArrayNotPossible($this->message))
        ;

        $this->converter2
            ->expects(self::once())
            ->method('objectToArray')
            ->with($this->message)
            ->willThrowException($unexpectedException)
        ;

        $this->converter3
            ->expects(self::never())
            ->method('objectToArray')
        ;

        $composite = new CompositeConverter();
        $composite->addConverter($this->converter1);
        $composite->addConverter($this->converter2);
        $composite->addConverter($this->converter3);

        $this->expectExceptionObject($expectedException);
        $composite->objectToArray($this->message);
    }

    public function testUnsuccessfulConvertingToArray(): void
    {
        $expectedException = new Event\Exception\ConversionToArrayNotPossible($this->message);

        $this->converter1
            ->expects(self::once())
            ->method('objectToArray')
            ->with($this->message)
            ->willThrowException(new Event\Exception\ConversionToArrayNotPossible($this->message))
        ;

        $this->converter2
            ->expects(self::once())
            ->method('objectToArray')
            ->with($this->message)
            ->willThrowException(new Event\Exception\ConversionToArrayNotPossible($this->message))
        ;

        $this->converter3
            ->expects(self::once())
            ->method('objectToArray')
            ->with($this->message)
            ->willThrowException(new Event\Exception\ConversionToArrayNotPossible($this->message))
        ;

        $composite = new CompositeConverter();
        $composite->addConverter($this->converter1);
        $composite->addConverter($this->converter2);
        $composite->addConverter($this->converter3);

        $this->expectExceptionObject($expectedException);
        $composite->objectToArray($this->message);
    }

    public function testConvertingToMessage(): void
    {
        $class = 'class';
        $data = ['test' => 'data'];

        $this->converter1
            ->expects(self::once())
            ->method('arrayToObject')
            ->with($data)
            ->willThrowException(new Event\Exception\ConversionToObjectNotPossible($data))
        ;

        $this->converter2
            ->expects(self::once())
            ->method('arrayToObject')
            ->with($data)
            ->willReturn($this->message)
        ;

        $this->converter3
            ->expects(self::never())
            ->method('arrayToObject')
        ;

        $composite = new CompositeConverter();
        $composite->addConverter($this->converter1);
        $composite->addConverter($this->converter2);
        $composite->addConverter($this->converter3);

        $result = $composite->arrayToObject($data);

        self::assertSame($this->message, $result);
    }

    public function testConvertingToMessageWithUnexpectedException(): void
    {
        $class = 'class';
        $data = ['test' => 'data'];

        $unexpectedException = new \InvalidArgumentException('Unexpected Exception.');
        $expectedException = new Event\Exception\ConversionToObjectNotPossible($data, $unexpectedException);

        $this->converter1
            ->expects(self::once())
            ->method('arrayToObject')
            ->with($data)
            ->willThrowException(new Event\Exception\ConversionToObjectNotPossible($data))
        ;

        $this->converter2
            ->expects(self::once())
            ->method('arrayToObject')
            ->with($data)
            ->willThrowException($unexpectedException)
        ;

        $this->converter3
            ->expects(self::never())
            ->method('arrayToObject')
        ;

        $composite = new CompositeConverter();
        $composite->addConverter($this->converter1);
        $composite->addConverter($this->converter2);
        $composite->addConverter($this->converter3);

        $this->expectExceptionObject($expectedException);
        $composite->arrayToObject($data);
    }

    public function testUnsuccessfulConvertingToMessage(): void
    {
        $class = 'class';
        $data = ['test' => 'data'];

        $expectedException = new Event\Exception\ConversionToObjectNotPossible($data);

        $this->converter1
            ->expects(self::once())
            ->method('arrayToObject')
            ->with($data)
            ->willThrowException(new Event\Exception\ConversionToObjectNotPossible($data))
        ;

        $this->converter2
            ->expects(self::once())
            ->method('arrayToObject')
            ->with($data)
            ->willThrowException(new Event\Exception\ConversionToObjectNotPossible($data))
        ;

        $this->converter3
            ->expects(self::once())
            ->method('arrayToObject')
            ->with($data)
            ->willThrowException(new Event\Exception\ConversionToObjectNotPossible($data))
        ;

        $composite = new CompositeConverter();
        $composite->addConverter($this->converter1);
        $composite->addConverter($this->converter2);
        $composite->addConverter($this->converter3);

        $this->expectExceptionObject($expectedException);

        $composite->arrayToObject($data);
    }
}
