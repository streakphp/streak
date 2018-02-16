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

namespace Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
final class Metadata
{
    private $metadata = [];

    private function __construct(array $metadata)
    {
        foreach ($metadata as $name => $value) {
            $this->set($name, $value);
        }
    }

    public function set(string $name, string $value)
    {
        $this->metadata[$name] = $value;
    }

    public function has(string $name)
    {
        if (isset($this->metadata[$name])) {
            return true;
        }

        return false;
    }

    public function get(string $name, string $default = null) : ?string
    {
        if (!$this->has($name)) {
            return $default;
        }

        return $this->metadata[$name];
    }

    public static function fromObject($object) : self
    {
        if (!isset($object->__streak_metadata)) {
            return new self([]);
        }

        if (!is_array($object->__streak_metadata)) {
            return new self([]);
        }

        return new self($object->__streak_metadata);
    }

    public static function clear(...$objects)
    {
        foreach ($objects as $object) {
            unset($object->__streak_metadata);
        }
    }

    public static function fromArray(array $metadata) : self
    {
        return new self($metadata);
    }

    public function toObject($object)
    {
        $object->__streak_metadata = $this->metadata;
    }

    public function toArray() : array
    {
        return $this->metadata;
    }

    public function empty() : bool
    {
        return 0 === count($this->metadata);
    }
}
