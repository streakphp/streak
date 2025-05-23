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

namespace Streak\Infrastructure\Domain\Event\Sourced\Subscription;

use Streak\Domain\Event\Listener\State;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @see \Streak\Infrastructure\Domain\Event\Sourced\Subscription\InMemoryStateTest
 */
class InMemoryState implements State
{
    private array $state = [];

    private function __construct()
    {
    }

    public function equals(object $state): bool
    {
        if (!$state instanceof State) {
            return false;
        }

        if ($this->toArray() !== $state->toArray()) {
            return false;
        }

        return true;
    }

    public function has(string $name): bool
    {
        return \array_key_exists($name, $this->state);
    }

    public function get(string $name)
    {
        if (false === $this->has($name)) {
            throw new \OutOfBoundsException(\sprintf('Value not found under key "%s"', $name));
        }

        return $this->state[$name];
    }

    public function set(string $name, $value): State
    {
        $this->validate($name, $value);

        $state = new self();
        $state->state = $this->state;
        $state->state[$name] = $value;

        self::ksort($state->state);

        return $state;
    }

    public function toArray(): array
    {
        return $this->state;
    }

    public static function fromArray(array $values): self
    {
        $state = new self();

        foreach ($values as $name => $value) {
            $state = $state->set($name, $value);
        }

        return $state;
    }

    public static function fromState(State $from): self
    {
        return self::fromArray($from->toArray());
    }

    public static function empty(): self
    {
        return new self();
    }

    private function validate(string $name, $value): void
    {
        if (true === empty($name)) {
            throw new \OutOfBoundsException('Name of value passed to state object must be non empty string.');
        }

        $value = [$name => $value];
        array_walk_recursive($value, function ($value, $key): void {
            if (true === \is_scalar($value)) {
                return;
            }
            if (null === $value) {
                return;
            }

            throw new \UnexpectedValueException(\sprintf('Values passed to state object can only be nulls & scalar values or recursive arrays of nulls & scalar values. Value of type "%s" given under key "%s".', \gettype($value), $key));
        });
    }

    /**
     * Sorts $array by its keys recursively.
     */
    private static function ksort(array &$array): void
    {
        foreach ($array as &$value) {
            if (true === \is_array($value)) {
                self::ksort($value);
            }
        }

        ksort($array);
    }
}
