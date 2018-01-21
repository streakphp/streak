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

namespace Streak\Infrastructure\Event\Converter;

use PHPUnit\Framework\TestCase;
use Streak\Domain;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Event\Converter\FlatObjectConverter
 */
class FlatObjectConverterTest extends TestCase
{
    public function testConverting()
    {
        $converter = new FlatObjectConverter();

        $message = new Event1Stub(
            'private', 'public', 'protected',
            0, 1, 2,
            0.0, 1.0, 2.0,
            ['private' => 'array', 1 => 'private array', 'private array' => 3, 'key' => ['private' => 'array', 1 => 'private array', 'private array' => 3], 0 => ['private' => 'array', 1 => 'private array', 'private array' => 3]],
            ['public' => 'array', 1 => 'public array', 'public array' => 3, 'key' => ['public' => 'array', 1 => 'public array', 'public array' => 3], 0 => ['public' => 'array', 1 => 'public array', 'public array' => 3]],
            ['protected' => 'array', 1 => 'protected array', 'protected array' => 3, 'key' => ['protected' => 'array', 1 => 'protected array', 'protected array' => 3], 0 => ['protected' => 'array', 1 => 'protected array', 'protected array' => 3]],
            null, null, null
        );

        $array = $converter->eventToArray($message);
        $result = $converter->arrayToEvent($array);

        $this->assertEquals($message, $result);
    }

    public function testConvertingObjectsWithinEvent()
    {
        $converter = new FlatObjectConverter();

        $message = new Event1Stub(
            'private', 'public', 'protected',
            0, 1, 2,
            0.0, 1.0, 2.0,
            [new \stdClass()], [], [],
            null, null, null
        );

        $exception = new Event\Exception\ConversionToArrayNotPossible($message, new \InvalidArgumentException());
        $this->expectExceptionObject($exception);

        $converter->eventToArray($message);
    }

    public function testConvertingArrayForInvalidClass()
    {
        $converter = new FlatObjectConverter();

        $array = [
            \stdClass::class => [],
        ];

        $exception = new Event\Exception\ConversionToEventNotPossible($array, new \InvalidArgumentException());
        $this->expectExceptionObject($exception);

        $converter->arrayToEvent($array);
    }

    public function testConvertingArrayWithInvalidProperty()
    {
        $converter = new FlatObjectConverter();

        $array = [
            Event1Stub::class => [
                'non_existent_property' => 'value',
            ],
        ];

        $exception = new Event\Exception\ConversionToEventNotPossible($array, new \InvalidArgumentException());
        $this->expectExceptionObject($exception);

        $converter->arrayToEvent($array);
    }
}

class Event1Stub implements Event
{
    public $publicStringProperty;
    public $publicIntegerProperty;
    public $publicFloatProperty;
    public $publicArrayProperty;
    public $publicAnyTypeProperty;
    public $publicEmptyArrayProperty;
    protected $protectedStringProperty;
    protected $protectedIntegerProperty;
    protected $protectedFloatProperty;
    protected $protectedArrayProperty;
    protected $protectedAnyTypeProperty;
    protected $protectedEmptyArrayProperty;
    private $privateStringProperty;
    private $privateIntegerProperty;
    private $privateFloatProperty;
    private $privateArrayProperty;
    private $privateAnyTypeProperty;
    private $privateEmptyArrayProperty;

    public function __construct(
        string $privateStringProperty,
        string $publicStringProperty,
        string $protectedStringProperty,
        int $privateIntegerProperty,
        int $publicIntegerProperty,
        int $protectedIntegerProperty,
        float $privateFloatProperty,
        float $publicFloatProperty,
        float $protectedFloatProperty,
        array $privateArrayProperty,
        array $publicArrayProperty,
        array $protectedArrayProperty,
        $privateAnyTypeProperty,
        $publicAnyTypeProperty,
        $protectedAnyTypeProperty
    ) {
        $this->privateStringProperty = $privateStringProperty;
        $this->publicStringProperty = $publicStringProperty;
        $this->protectedStringProperty = $protectedStringProperty;

        $this->privateIntegerProperty = $privateIntegerProperty;
        $this->publicIntegerProperty = $publicIntegerProperty;
        $this->protectedIntegerProperty = $protectedIntegerProperty;

        $this->privateFloatProperty = $privateFloatProperty;
        $this->publicFloatProperty = $publicFloatProperty;
        $this->protectedFloatProperty = $protectedFloatProperty;

        $this->privateArrayProperty = $privateArrayProperty;
        $this->publicArrayProperty = $publicArrayProperty;
        $this->protectedArrayProperty = $protectedArrayProperty;

        $this->privateAnyTypeProperty = $privateAnyTypeProperty;
        $this->publicAnyTypeProperty = $publicAnyTypeProperty;
        $this->protectedAnyTypeProperty = $protectedAnyTypeProperty;

        $this->privateEmptyArrayProperty = [];
        $this->publicEmptyArrayProperty = [];
        $this->protectedEmptyArrayProperty = [];
    }

    public function producerId() : Domain\Id
    {
    }
}
