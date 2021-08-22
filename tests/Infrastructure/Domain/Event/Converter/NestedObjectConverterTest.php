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

use PHPUnit\Framework\TestCase;
use Streak\Domain;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\Event\Converter\NestedObjectConverter
 */
class NestedObjectConverterTest extends TestCase
{
    public function testConverting(): void
    {
        $converter = new NestedObjectConverter();

        $event = new Event1Stub(
            'private',
            'public',
            'protected',
            0,
            1,
            2,
            0.0,
            1.0,
            2.0,
            ['private' => 'array', 1 => 'private array', 'private array' => 3, 'key' => ['private' => 'array', 1 => 'private array', 'private array' => 3], 0 => ['private' => 'array', 1 => 'private array', 'private array' => 3]],
            ['public' => 'array', 1 => 'public array', 'public array' => 3, 'key' => ['public' => 'array', 1 => 'public array', 'public array' => 3], 0 => ['public' => 'array', 1 => 'public array', 'public array' => 3]],
            ['protected' => 'array', 1 => 'protected array', 'protected array' => 3, 'key' => ['protected' => 'array', 1 => 'protected array', 'protected array' => 3], 0 => ['protected' => 'array', 1 => 'protected array', 'protected array' => 3]],
            null,
            null,
            null
        );

        $array = $converter->objectToArray($event);
        $result = $converter->arrayToObject($array);

        self::assertEquals($event, $result);
    }

    public function testConvertingWithInheritedProperties(): void
    {
        $converter = new NestedObjectConverter();

        $event = new EventB('property1', 'property2');

        $array = $converter->objectToArray($event);
        $result = $converter->arrayToObject($array);

        self::assertEquals($event, $result);
    }

    public function testConvertingEventsWithinEvents(): void
    {
        $converter = new NestedObjectConverter();

        $event = new EventC(new EventB('property1', 'property2'));

        $array = $converter->objectToArray($event);
        $result = $converter->arrayToObject($array);

        self::assertEquals($event, $result);
    }

    public function testConvertingArrayWithInvalidProperty(): void
    {
        $converter = new NestedObjectConverter();

        $array = [
            Event1Stub::class => [
                'non_existent_property' => 'value',
            ],
        ];

        $exception = new Event\Exception\ConversionToObjectNotPossible($array, new \InvalidArgumentException());
        $this->expectExceptionObject($exception);

        $converter->arrayToObject($array);
    }

    public function testNestedObjectToArray(): void
    {
        $converter = new NestedObjectConverter();
        $object = new ParentObject(
            'parent',
            new ChildObject(
                'child',
                ['foo' => 'bar'],
                new ChildObject('child2', [1])
            ),
            ['foo']
        );
        $expectedArray = [
            ParentObject::class => [
                'objectProperty' => [
                    ChildObject::class => [
                        'scalarProperty' => 'child',
                        'arrayProperty' => [
                            'foo' => 'bar',
                        ],
                        'child' => [
                            ChildObject::class => [
                                'scalarProperty' => 'child2',
                                'arrayProperty' => [
                                    0 => 1,
                                ],
                                'child' => null,
                            ],
                        ],
                    ],
                ],
                'scalarProperty' => 'parent',
                'arrayProperty' => [
                    0 => 'foo',
                ],
            ],
        ];
        self::assertEquals($expectedArray, $converter->objectToArray($object));
    }

    public function testNestedArrayToObject(): void
    {
        $converter = new NestedObjectConverter();

        $expectedObject = new ParentObject(
            'parent',
            new ChildObject(
                'child',
                ['foo' => 'bar'],
                new ChildObject('child2', [1])
            ),
            ['foo']
        );

        $array = [
            ParentObject::class => [
                'objectProperty' => [
                    ChildObject::class => [
                        'scalarProperty' => 'child',
                        'arrayProperty' => [
                            'foo' => 'bar',
                        ],
                        'child' => [
                            ChildObject::class => [
                                'scalarProperty' => 'child2',
                                'arrayProperty' => [
                                    0 => 1,
                                ],
                                'child' => null,
                            ],
                        ],
                    ],
                ],
                'scalarProperty' => 'parent',
                'arrayProperty' => [
                    0 => 'foo',
                ],
            ],
        ];

        self::assertEquals($expectedObject, $converter->arrayToObject($array));
    }

    public function testItDoesntConvertWrongNestedType(): void
    {
        $this->expectException(Event\Exception\ConversionToArrayNotPossible::class);
        $converter = new NestedObjectConverter();
        $converter->objectToArray(new NestedResource(tmpfile()));
    }
}

class NestedResource
{
    /**
     * @param resource $resource
     */
    public function __construct(private $resource)
    {
    }
}

class Event1Stub implements Event
{
    public array $publicEmptyArrayProperty;
    protected array $protectedEmptyArrayProperty;
    private array $privateEmptyArrayProperty;

    public function __construct(
        private string $privateStringProperty,
        public string $publicStringProperty,
        protected string $protectedStringProperty,
        private int $privateIntegerProperty,
        public int $publicIntegerProperty,
        protected int $protectedIntegerProperty,
        private float $privateFloatProperty,
        public float $publicFloatProperty,
        protected float $protectedFloatProperty,
        private array $privateArrayProperty,
        public array $publicArrayProperty,
        protected array $protectedArrayProperty,
        private $privateAnyTypeProperty,
        public $publicAnyTypeProperty,
        protected $protectedAnyTypeProperty
    ) {
        $this->privateEmptyArrayProperty = [];
        $this->publicEmptyArrayProperty = [];
        $this->protectedEmptyArrayProperty = [];
    }

    public function producerId(): Domain\Id
    {
    }
}

class EventA implements Event
{
    public function __construct(private string $property1)
    {
    }
}

class EventB extends EventA
{
    public function __construct($property1, private $property2)
    {
        parent::__construct($property1);
    }
}

class EventC implements Event
{
    public function __construct(private Event $event)
    {
    }
}

class ParentObject
{
    public function __construct(private string $scalarProperty, private object $objectProperty, private array $arrayProperty)
    {
    }
}

class ChildObject
{
    public function __construct(private string $scalarProperty, private array $arrayProperty, private ?self $child = null)
    {
    }
}
