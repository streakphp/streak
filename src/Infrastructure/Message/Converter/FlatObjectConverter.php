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

use Streak\Domain\Message;
use Streak\Domain\Message\Converter;
use Streak\Domain\Message\Exception;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class FlatObjectConverter implements Converter
{
    /**
     * @throws Exception\ConversionToArrayNotPossible
     */
    public function messageToArray(Message $message) : array
    {
        try {
            return $this->toArray($message);
        } catch (\Exception $exception) {
            throw new Exception\ConversionToArrayNotPossible($message, $exception);
        }
    }

    /**
     * @throws Exception\ConversionToMessageNotPossible
     */
    public function arrayToMessage(string $class, array $data) : Message
    {
        try {
            return $this->toObject($class, $data);
        } catch (\Exception $exception) {
            throw new Exception\ConversionToMessageNotPossible($class, $data, $exception);
        }
    }

    private function toArray($message) : array
    {
        $object = $message;
        if (is_object($object)) { // converts object to array
            $reflection = new \ReflectionObject($object);
            $object = [];
            foreach ($reflection->getProperties() as $property) {
                $isPublic = $property->isPublic();
                if (!$isPublic) {
                    $property->setAccessible(true);
                }

                $object[$property->getName()] = $property->getValue($message);

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

    private function toObject(string $class, array $array) : Message
    {
        $reflection = new \ReflectionClass($class);
        $message = $reflection->newInstanceWithoutConstructor();

        if (!$message instanceof Message) {
            throw new \InvalidArgumentException();
        }

        $reflection = new \ReflectionObject($message);
        foreach ($array as $name => $value) {
            if (!$reflection->hasProperty($name)) {
                throw new \InvalidArgumentException();
            }

            $property = $reflection->getProperty($name);

            $isPublic = $property->isPublic();
            if (!$isPublic) {
                $property->setAccessible(true);
            }

            $property->setValue($message, $value);

            if (!$isPublic) {
                $property->setAccessible(false);
            }
        }

        return $message;
    }
}
