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
 *
 * @see \Streak\Domain\Event\EnvelopeTest
 */
final class Envelope implements Domain\Envelope
{
    public const METADATA_UUID = 'uuid';
    public const METADATA_NAME = 'name';
    public const METADATA_VERSION = 'version';
    public const METADATA_PRODUCER_TYPE = 'producer_type';
    public const METADATA_PRODUCER_ID = 'producer_id';

    private Event $message;
    private array $metadata = [];

    public function __construct(UUID $uuid, string $name, Event $message, Domain\Id $producerId, ?int $version = null)
    {
        $this->metadata[self::METADATA_UUID] = $uuid->toString();
        $this->metadata[self::METADATA_NAME] = $name;
        $this->message = $message;
        $this->metadata[self::METADATA_PRODUCER_TYPE] = \get_class($producerId);
        $this->metadata[self::METADATA_PRODUCER_ID] = $producerId->toString();
        if (null !== $version) {
            $this->metadata[self::METADATA_VERSION] = $version;
        }
    }

    public static function new(Event $event, Domain\Id $producerId, ?int $version = null)
    {
        return new self(UUID::random(), \get_class($event), $event, $producerId, $version);
    }

    public function uuid(): UUID
    {
        return new UUID($this->get(self::METADATA_UUID));
    }

    public function name(): string
    {
        return $this->get(self::METADATA_NAME);
    }

    public function message(): Event
    {
        return $this->message;
    }

    public function producerId(): Domain\Id
    {
        return $this->get(self::METADATA_PRODUCER_TYPE)::fromString($this->get(self::METADATA_PRODUCER_ID));
    }

    public function version(): ?int
    {
        return $this->get(self::METADATA_VERSION);
    }

    public function set(string $name, $value): self
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

        $new->metadata = $this->metadata;
        $new->metadata[$name] = $value;

        return $new;
    }

    public function get($name)
    {
        return $this->metadata[$name] ?? null;
    }

    public function metadata(): array
    {
        return $this->metadata;
    }

    public function equals(object $envelope): bool
    {
        if (!$envelope instanceof static) {
            return false;
        }

        if (!$this->uuid()->equals($envelope->uuid())) { // in a way envelope is an entity containing value object which event is.
            return false;
        }

        return true;
    }
}
