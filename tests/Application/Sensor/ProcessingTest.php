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

namespace Streak\Application\Sensor;

use PHPUnit\Framework\TestCase;
use Streak\Application\Sensor;
use Streak\Application\Sensor\ProcessingTest\AProcessed;
use Streak\Application\Sensor\ProcessingTest\ArrayProcessed;
use Streak\Application\Sensor\ProcessingTest\B1;
use Streak\Application\Sensor\ProcessingTest\B1Processed;
use Streak\Application\Sensor\ProcessingTest\B2;
use Streak\Application\Sensor\ProcessingTest\B2Processed;
use Streak\Application\Sensor\ProcessingTest\BooleanProcessed;
use Streak\Application\Sensor\ProcessingTest\IntegerProcessed;
use Streak\Application\Sensor\ProcessingTest\SensorStub1;
use Streak\Application\Sensor\ProcessingTest\SensorStub2;
use Streak\Application\Sensor\ProcessingTest\StdClassProcessed;
use Streak\Application\Sensor\ProcessingTest\StringProcessed;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Application\Sensor\Processing
 */
class ProcessingTest extends TestCase
{
    /**
     * @var Sensor\Id|\PHPUnit_Framework_MockObject_MockObject
     */
    private $id;

    public function setUp() : void
    {
        $this->id = $this->getMockBuilder(Sensor\Id::class)->getMockForAbstractClass();
    }

    public function testObject()
    {
        $sensor = new SensorStub1($this->id);

        $this->assertSame($this->id, $sensor->id());
        $this->assertSame($this->id, $sensor->producerId());
        $this->assertSame($this->id, $sensor->sensorId());
        $this->assertNull($sensor->last());
        $this->assertEmpty($sensor->events());

        $array = ['example' => 'array'];
        $sensor->process($array);
        $integer = 1;
        $sensor->process($integer);
        $string = 'string';
        $sensor->process($string);
        $b1 = new B1();
        $sensor->process($b1);
        $b2 = new B2();
        $sensor->process($b2);
        $boolean = true;
        $sensor->process($boolean);
        $stdClass = new \stdClass();
        $sensor->process($stdClass);

        $this->assertEquals([new ArrayProcessed($array), new IntegerProcessed(1), new StringProcessed($string), new B1Processed($b1), new B2Processed($b2), new BooleanProcessed($boolean), new StdClassProcessed($stdClass)], $sensor->events());

        $sensor = new SensorStub1($this->id);
        $sensor->process($array, $integer, $string, $b1, $b2, $boolean, $stdClass);

        $this->assertEquals([new ArrayProcessed($array), new IntegerProcessed(1), new StringProcessed($string), new B1Processed($b1), new B2Processed($b2), new BooleanProcessed($boolean), new StdClassProcessed($stdClass)], $sensor->events());

        $sensor = new SensorStub2($this->id);

        $b1 = new B1();
        $sensor->process($b1);
        $b2 = new B2();
        $sensor->process($b2);

        $this->assertEquals([new AProcessed($b1), new AProcessed($b2)], $sensor->events());

        $sensor = new SensorStub2($this->id);

        $b1 = new B1();
        $b2 = new B2();
        $sensor->process($b1, $b2);

        $this->assertEquals([new AProcessed($b1), new AProcessed($b2)], $sensor->events());
    }

    public function testMoreThanOneProcessingMethod()
    {
        $sensor = new SensorStub2($this->id);

        try {
            $integer = 1;
            $sensor->process($integer);
        } catch (\BadMethodCallException $e) {
            $this->assertSame('Too many processing functions found.', $e->getMessage());
            $this->assertEmpty($sensor->events());
        }

        // no new assertions here please
    }

    public function testTransactionalityOfProcessMethod()
    {
        $sensor = new SensorStub2($this->id);

        try {
            $b1 = new B1();
            $integer = 1;
            $sensor->process($b1, 'string');
        } catch (\RuntimeException $e) {
            $this->assertSame('Thrown inside processStringAndThrowAnException method.', $e->getMessage());
            $this->assertEmpty($sensor->events());
        }

        // no new assertions here please
    }
}

namespace Streak\Application\Sensor\ProcessingTest;

use Streak\Application\Sensor;
use Streak\Domain\Event;

class SensorStub1 implements Sensor
{
    use Sensor\Identification;
    use Sensor\Processing;

    public function processArray(array $message) : void
    {
        $this->addEvent(new ArrayProcessed($message));
    }

    public function processInteger(int $integer)
    {
        $this->addEvent(new IntegerProcessed($integer));
    }

    public function processString(string $string)
    {
        $this->addEvent(new StringProcessed($string));
    }

    public function processB1(B1 $b1)
    {
        $this->addEvent(new B1Processed($b1));
    }

    public function processB2(B2 $b2)
    {
        $this->addEvent(new B2Processed($b2));
    }

    public function processBoolean(bool $boolean)
    {
        $this->addEvent(new BooleanProcessed($boolean));
    }

    public function processStdClass(\stdClass $stdClass)
    {
        $this->addEvent(new StdClassProcessed($stdClass));
    }
}

class SensorStub2 implements Sensor
{
    use Sensor\Identification;
    use Sensor\Processing;

    public function processB1(B1 $b1, $notNeededSecondArgument)
    {
        $this->addEvent(new B1Processed($b1));
    }

    public function processOptionalB2(?B2 $optionalB1)
    {
        $this->addEvent(new B1Processed($optionalB1));
    }

    public function processNullableB2(B2 $nullableB1 = null)
    {
        $this->addEvent(new B1Processed($nullableB1));
    }

    public function processA(A $a)
    {
        $this->addEvent(new AProcessed($a));
    }

    public function processInteger(int $integer)
    {
        $this->addEvent(new IntegerProcessed($integer));
    }

    public function processAnotherInteger(int $integer)
    {
        $this->addEvent(new IntegerProcessed($integer));
    }

    public function processStringAndThrowAnException(string $string)
    {
        throw new \RuntimeException('Thrown inside processStringAndThrowAnException method.');
    }
}

class ArrayProcessed implements Event
{
    private array $array;

    public function __construct(array $array)
    {
        $this->array = $array;
    }
}

class IntegerProcessed implements Event
{
    private int $integer;

    public function __construct(int $array)
    {
        $this->integer = $array;
    }
}

class StringProcessed implements Event
{
    private string $string;

    public function __construct(string $string)
    {
        $this->string = $string;
    }
}

class StdClassProcessed implements Event
{
    private \stdClass $stdClass;

    public function __construct(\stdClass $stdClass)
    {
        $this->stdClass = $stdClass;
    }
}

class BooleanProcessed implements Event
{
    private bool $boolean;

    public function __construct(bool $boolean)
    {
        $this->boolean = $boolean;
    }
}

class A implements Event
{
}

class B1 extends A
{
}

class B2 extends A
{
}

class AProcessed implements Event
{
    private A $a;

    public function __construct(A $a)
    {
        $this->a = $a;
    }
}

class B2Processed implements Event
{
    private B2 $b2;

    public function __construct(B2 $b2)
    {
        $this->b2 = $b2;
    }
}

class B1Processed implements Event
{
    private B1 $b1;

    public function __construct(B1 $b1)
    {
        $this->b1 = $b1;
    }
}
