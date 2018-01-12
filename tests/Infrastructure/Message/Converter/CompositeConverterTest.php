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

namespace Streak\Infrastructure\Message\Converter;

use PHPUnit\Framework\TestCase;
use Streak\Domain\Message;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Message\Converter\CompositeConverter
 */
class CompositeConverterTest extends TestCase
{
    /**
     * @var Message\Converter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $converter1;

    /**
     * @var Message\Converter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $converter2;

    /**
     * @var Message\Converter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $converter3;

    /**
     * @var Message|\PHPUnit_Framework_MockObject_MockObject
     */
    private $message;

    protected function setUp()
    {
        $this->converter1 = $this->getMockBuilder(Message\Converter::class)->getMockForAbstractClass();
        $this->converter2 = $this->getMockBuilder(Message\Converter::class)->getMockForAbstractClass();
        $this->converter3 = $this->getMockBuilder(Message\Converter::class)->getMockForAbstractClass();

        $this->message = $this->getMockBuilder(Message::class)->getMockForAbstractClass();
    }

    public function testConvertingToArray()
    {
        $data = ['test' => 'data'];

        $this->converter1
            ->expects($this->once())
            ->method('messageToArray')
            ->with($this->message)
            ->willThrowException(new Message\Exception\ConversionToArrayNotPossible($this->message))
        ;

        $this->converter2
            ->expects($this->once())
            ->method('messageToArray')
            ->with($this->message)
            ->willReturn($data)
        ;

        $this->converter3
            ->expects($this->never())
            ->method('messageToArray')
        ;

        $composite = new CompositeConverter();
        $composite->addConverter($this->converter1);
        $composite->addConverter($this->converter2);
        $composite->addConverter($this->converter3);

        $result = $composite->messageToArray($this->message);

        $this->assertSame($data, $result);
    }

    public function testConvertingToArrayWithUnexpectedException()
    {
        $unexpectedException = new \InvalidArgumentException('Unexpected Exception.');
        $expectedException = new Message\Exception\ConversionToArrayNotPossible($this->message, $unexpectedException);

        $this->converter1
            ->expects($this->once())
            ->method('messageToArray')
            ->with($this->message)
            ->willThrowException(new Message\Exception\ConversionToArrayNotPossible($this->message))
        ;

        $this->converter2
            ->expects($this->once())
            ->method('messageToArray')
            ->with($this->message)
            ->willThrowException($unexpectedException)
        ;

        $this->converter3
            ->expects($this->never())
            ->method('messageToArray')
        ;

        $composite = new CompositeConverter();
        $composite->addConverter($this->converter1);
        $composite->addConverter($this->converter2);
        $composite->addConverter($this->converter3);

        $this->expectExceptionObject($expectedException);
        $composite->messageToArray($this->message);
    }

    public function testUnsuccessfulConvertingToArray()
    {
        $expectedException = new Message\Exception\ConversionToArrayNotPossible($this->message);

        $this->converter1
            ->expects($this->once())
            ->method('messageToArray')
            ->with($this->message)
            ->willThrowException(new Message\Exception\ConversionToArrayNotPossible($this->message))
        ;

        $this->converter2
            ->expects($this->once())
            ->method('messageToArray')
            ->with($this->message)
            ->willThrowException(new Message\Exception\ConversionToArrayNotPossible($this->message))
        ;

        $this->converter3
            ->expects($this->once())
            ->method('messageToArray')
            ->with($this->message)
            ->willThrowException(new Message\Exception\ConversionToArrayNotPossible($this->message))
        ;

        $composite = new CompositeConverter();
        $composite->addConverter($this->converter1);
        $composite->addConverter($this->converter2);
        $composite->addConverter($this->converter3);

        $this->expectExceptionObject($expectedException);
        $composite->messageToArray($this->message);
    }

    public function testConvertingToMessage()
    {
        $class = 'class';
        $data = ['test' => 'data'];

        $this->converter1
            ->expects($this->once())
            ->method('arrayToMessage')
            ->with($class, $data)
            ->willThrowException(new Message\Exception\ConversionToMessageNotPossible($class, $data))
        ;

        $this->converter2
            ->expects($this->once())
            ->method('arrayToMessage')
            ->with($class, $data)
            ->willReturn($this->message)
        ;

        $this->converter3
            ->expects($this->never())
            ->method('arrayToMessage')
        ;

        $composite = new CompositeConverter();
        $composite->addConverter($this->converter1);
        $composite->addConverter($this->converter2);
        $composite->addConverter($this->converter3);

        $result = $composite->arrayToMessage($class, $data);

        $this->assertSame($this->message, $result);
    }

    public function testConvertingToMessageWithUnexpectedException()
    {
        $class = 'class';
        $data = ['test' => 'data'];

        $unexpectedException = new \InvalidArgumentException('Unexpected Exception.');
        $expectedException = new Message\Exception\ConversionToMessageNotPossible($class, $data, $unexpectedException);

        $this->converter1
            ->expects($this->once())
            ->method('arrayToMessage')
            ->with($class, $data)
            ->willThrowException(new Message\Exception\ConversionToMessageNotPossible($class, $data))
        ;

        $this->converter2
            ->expects($this->once())
            ->method('arrayToMessage')
            ->with($class, $data)
            ->willThrowException($unexpectedException)
        ;

        $this->converter3
            ->expects($this->never())
            ->method('arrayToMessage')
        ;

        $composite = new CompositeConverter();
        $composite->addConverter($this->converter1);
        $composite->addConverter($this->converter2);
        $composite->addConverter($this->converter3);

        $this->expectExceptionObject($expectedException);
        $composite->arrayToMessage($class, $data);
    }

    public function testUnsuccessfulConvertingToMessage()
    {
        $class = 'class';
        $data = ['test' => 'data'];

        $expectedException = new Message\Exception\ConversionToMessageNotPossible($class, $data);

        $this->converter1
            ->expects($this->once())
            ->method('arrayToMessage')
            ->with($class, $data)
            ->willThrowException(new Message\Exception\ConversionToMessageNotPossible($class, $data))
        ;

        $this->converter2
            ->expects($this->once())
            ->method('arrayToMessage')
            ->with($class, $data)
            ->willThrowException(new Message\Exception\ConversionToMessageNotPossible($class, $data))
        ;

        $this->converter3
            ->expects($this->once())
            ->method('arrayToMessage')
            ->with($class, $data)
            ->willThrowException(new Message\Exception\ConversionToMessageNotPossible($class, $data))
        ;

        $composite = new CompositeConverter();
        $composite->addConverter($this->converter1);
        $composite->addConverter($this->converter2);
        $composite->addConverter($this->converter3);

        $this->expectExceptionObject($expectedException);

        $composite->arrayToMessage($class, $data);
    }
}
