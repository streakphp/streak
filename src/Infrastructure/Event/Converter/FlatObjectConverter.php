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

use Streak\Domain\Event;
use Streak\Domain\Event\Converter;
use Streak\Domain\Event\Exception;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class FlatObjectConverter implements Converter
{
    /**
     * @throws Exception\ConversionToArrayNotPossible
     */
    public function eventToArray(Event $event) : array
    {
        try {
            $class = get_class($event);
            $array = $this->toArray($event);
            $array = [$class => $array];

            return $array;
        } catch (\Exception $exception) {
            throw new Exception\ConversionToArrayNotPossible($event, $exception);
        }
    }

    /**
     * @throws Exception\ConversionToEventNotPossible
     */
    public function arrayToEvent(array $data) : Event
    {
        try {
            return $this->toObject($data);
        } catch (\Exception $exception) {
            throw new Exception\ConversionToEventNotPossible($data, $exception);
        }
    }

    private function toArray($event) : array
    {
        $object = $event;
        if (is_object($object)) { // converts object to array
            $reflection = new \ReflectionObject($object);
            $object = [];
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
        }

        if (is_array($object)) {
            foreach ($object as &$value) {
                if (is_object($value)) {
                    throw new \InvalidArgumentException();
                }
                if (is_scalar($value)) {
                    continue;
                }
                if (is_null($value)) {
                    continue;
                }
                $value = $this->toArray($value);
            }
        }

        return $object;
    }

    private function toObject(array $array) : Event
    {
        $class = array_keys($array)[0];
        $array = $array[$class];

        $reflection = new \ReflectionClass($class);
        $event = $reflection->newInstanceWithoutConstructor();

        if (!$event instanceof Event) {
            throw new \InvalidArgumentException();
        }

        $reflection = new \ReflectionObject($event);
        foreach ($array as $name => $value) {
            if (!$reflection->hasProperty($name)) {
                throw new \InvalidArgumentException();
            }

            $property = $reflection->getProperty($name);

            $isPublic = $property->isPublic();
            if (!$isPublic) {
                $property->setAccessible(true);
            }

            $property->setValue($event, $value);

            if (!$isPublic) {
                $property->setAccessible(false);
            }
        }

        return $event;
    }
}