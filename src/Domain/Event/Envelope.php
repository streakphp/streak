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

use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Id\UUID;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
final class Envelope implements Domain\Envelope
{
    private const ATTRIBUTE_UUID = '__uuid__';
    private const ATTRIBUTE_NAME = '__name__';
    private const ATTRIBUTE_VERSION = '__version__';

    private $message;
    private $producerId;
    private $attributes;

    public function __construct(UUID $uuid, string $name, Event $message, Domain\Id $producerId, ?int $version = null)
    {
        $this->attributes[self::ATTRIBUTE_UUID] = $uuid->toString();
        $this->attributes[self::ATTRIBUTE_NAME] = $name;
        $this->message = $message;
        $this->producerId = $producerId;
        $this->attributes[self::ATTRIBUTE_VERSION] = $version;
    }

    public static function new(Event $event, Domain\Id $producerId, ?int $version = null)
    {
        return new self(UUID::random(), get_class($event), $event, $producerId, $version);
    }

    public function uuid() : UUID
    {
        return new UUID($this->get(self::ATTRIBUTE_UUID));
    }

    public function name() : string
    {
        return $this->get(self::ATTRIBUTE_NAME);
    }

    public function message() : Domain\Event
    {
        return $this->message;
    }

    public function producerId() : Domain\Id
    {
        return $this->producerId;
    }

    public function version() : ?int
    {
        return $this->get(self::ATTRIBUTE_VERSION);
    }

    public function set(string $name, $value) : self
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Name of the attribute can not be empty.');
        }
        if (!is_scalar($value)) {
            throw new \InvalidArgumentException(sprintf('Value for attribute "%s" is a scalar.', $name));
        }

        $new = new self(
            $this->uuid(),
            $this->name(),
            $this->message(),
            $this->producerId(),
            $this->version()
        );

        $new->attributes = $this->attributes;
        $new->attributes[$name] = $value;

        return $new;
    }

    public function get($name)
    {
        return $this->attributes[$name] ?? null;
    }

    public function metadata() : array
    {
        return $this->attributes;
    }
}
