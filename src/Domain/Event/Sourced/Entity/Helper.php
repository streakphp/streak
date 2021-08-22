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

namespace Streak\Domain\Event\Sourced\Entity;

use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Domain\Event\Sourced\Entity\HelperTest
 */
final class Helper
{
    private function __construct(private object $object)
    {
    }

    public static function for(object $object): self
    {
        return new self($object);
    }

    /**
     * @throws Event\Exception\NoEventApplyingMethodFound
     * @throws Event\Exception\TooManyEventApplyingMethodsFound
     * @throws \Throwable
     */
    public function applyEvent(Event\Envelope $event): void
    {
        self::applyEventByArgumentType($event, $this->object);
    }

    /**
     * @return Event\Sourced\Entity[]
     */
    public function extractEventSourcedEntities(): iterable
    {
        yield from self::doExtractEventSourcedEntities($this->object);
    }

    private static function applyEventByArgumentType(Event\Envelope $event, object &$entity): void
    {
        $reflection = new \ReflectionObject($entity);

        $methods = [];
        foreach ($reflection->getMethods() as $method) {
            // Method name must start with "apply"
            if ('apply' !== mb_substr($method->getName(), 0, 5)) {
                continue;
            }

            // ...and have exactly one parameter...
            if (1 !== $method->getNumberOfParameters()) {
                continue;
            }

            // ...which is required...
            if (1 !== $method->getNumberOfRequiredParameters()) {
                continue;
            }

            $parameter = $method->getParameters()[0];
            $parameter = $parameter->getType();

            // ...is not an union...
            if (!$parameter instanceof \ReflectionNamedType) {
                continue;
            }

            $parameter = $parameter->getName();
            $parameter = new \ReflectionClass($parameter);

            // ..and its an event...
            if (false === $parameter->isSubclassOf(Event::class)) {
                continue;
            }

            $target = new \ReflectionClass($event->message());

            // .. and $event is type or subtype of defined $parameter
            while ($parameter->getName() !== $target->getName()) {
                $target = $target->getParentClass();

                if (false === $target) {
                    continue 2;
                }
            }

            $methods[] = $method;
        }

        if (0 === \count($methods)) {
            throw new Event\Exception\NoEventApplyingMethodFound($entity, $event);
        }

        // TODO: filter methods matching given event exactly and if it wont work, than filter by direct ascendants of given event and so on

        if (\count($methods) > 1) {
            throw new Event\Exception\TooManyEventApplyingMethodsFound($entity, $event);
        }

        $method = array_shift($methods);

        $isPublic = $method->isPublic();
        if (false === $isPublic) {
            $method->setAccessible(true);
        }

        try {
            $method->invoke($entity, $event->message());
        } finally {
            if (false === $isPublic) {
                $method->setAccessible(false);
            }
        }
    }

    /**
     * Extract event sourced entities recursively.
     */
    private static function doExtractEventSourcedEntities(object $object, array &$ignored = []): iterable
    {
        $ignored[] = $object;

        $reflection = new \ReflectionObject($object);
        foreach ($reflection->getProperties() as $property) {
            $public = $property->isPublic();

            if (false === $public) {
                $property->setAccessible(true);
            }

            if (false === $property->isInitialized($object)) {
                continue;
            }

            $entity = $property->getValue($object);

            if (false === $public) {
                $property->setAccessible(false);
            }

            if (true === is_iterable($entity)) {
                $entities = $entity;
            } else {
                $entities = [$entity];
            }

            foreach ($entities as $entity) {
                if (!$entity instanceof Event\Sourced\Entity) {
                    continue;
                }

                foreach ($ignored as $ignore) {
                    if ($entity->equals($ignore)) {
                        continue 2;
                    }
                }

                yield $entity;

                foreach (self::doExtractEventSourcedEntities($entity, $ignored) as $entity) {
                    yield $entity;
                }
            }
        }
    }
}
