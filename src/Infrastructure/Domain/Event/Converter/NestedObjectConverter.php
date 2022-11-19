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

use Streak\Domain\Event;
use Streak\Domain\Event\Converter;
use Streak\Domain\Event\Exception;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Domain\Event\Converter\NestedObjectConverterTest
 */
class NestedObjectConverter implements Converter
{
    /**
     * @throws Exception\ConversionToArrayNotPossible
     */
    public function objectToArray(object $object): array
    {
        try {
            $class = $object::class;
            $array = $this->toArray($object);

            return [$class => $array];
        } catch (\Exception $exception) {
            throw new Exception\ConversionToArrayNotPossible($object, $exception);
        }
    }

    /**
     * @throws Exception\ConversionToObjectNotPossible
     */
    public function arrayToObject(array $data): object
    {
        try {
            return $this->toObject($data);
        } catch (\Exception $exception) {
            throw new Exception\ConversionToObjectNotPossible($data, $exception);
        }
    }

    private function toArray($event): array
    {
        $object = $event;
        if (\is_object($object)) { // converts object to array
            $reflection = new \ReflectionObject($object);
            $object = [];
            do {
                foreach ($reflection->getProperties() as $property) {
                    $isPublic = $property->isPublic();
                    if (!$isPublic) {
                        $property->setAccessible(true);
                    }

                    $object[$property->getName()] = $property->getValue($event);

                    if (!$isPublic) {
                        $property->setAccessible(false);
                    }
                }
                $reflection = $reflection->getParentClass();

                if (false === $reflection) {
                    break;
                }
            } while ($reflection->getProperties() > 0);
        }

        if (\is_array($object)) {
            foreach ($object as &$value) {
                if (\is_object($value)) {
                    $value = $this->objectToArray($value);

                    continue;
                }
                if (\is_scalar($value)) {
                    continue;
                }
                if (null === $value) {
                    continue;
                }
                if (\is_array($value)) {
                    $value = $this->toArray($value);

                    continue;
                }

                throw new Exception\NotSupportedType($value);
            }
        }

        return $object;
    }

    private function toObject(array $array): object
    {
        $class = array_keys($array)[0];
        $array = $array[$class];

        $reflection = new \ReflectionClass($class);
        /** @var Event $event */
        $event = $reflection->newInstanceWithoutConstructor();

        $reflection = new \ReflectionObject($event);
        foreach ($array as $name => $value) {
            $current = $reflection;

            while (false === $current->hasProperty($name)) {
                $current = $current->getParentClass();

                if (false === $current) {
                    throw new \InvalidArgumentException('Property not found.');
                }
            }

            $property = $current->getProperty($name);

            $isPublic = $property->isPublic();
            if (!$isPublic) {
                $property->setAccessible(true);
            }

            $value = $this->convertIfEvent($value);

            $property->setValue($event, $value);

            if (!$isPublic) {
                $property->setAccessible(false);
            }
        }

        return $event;
    }

    private function convertIfEvent($value)
    {
        if (!\is_array($value)) {
            return $value;
        }

        if (1 !== \count($value)) {
            return $value;
        }

        reset($value);
        $class = key($value);

        if (!\is_string($class)) {
            return $value;
        }

        if (!class_exists($class)) {
            return $value;
        }

        return $this->arrayToObject($value);
    }
}
