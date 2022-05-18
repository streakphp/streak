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
 * @template T of Event
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
    public const METADATA_ENTITY_TYPE = 'entity_type';
    public const METADATA_ENTITY_ID = 'entity_id';
    private array $metadata = [];

    /**
     * @param T $message
     */
    public function __construct(UUID $uuid, string $name, private Event $message, Domain\Id $producerId, Domain\Id $entityId, ?int $version = null)
    {
        $this->metadata[self::METADATA_UUID] = $uuid->toString();
        $this->metadata[self::METADATA_NAME] = $name;
        $this->metadata[self::METADATA_PRODUCER_TYPE] = $producerId::class;
        $this->metadata[self::METADATA_PRODUCER_ID] = $producerId->toString();
        $this->metadata[self::METADATA_ENTITY_TYPE] = $entityId::class;
        $this->metadata[self::METADATA_ENTITY_ID] = $entityId->toString();
        if (null !== $version) {
            $this->metadata[self::METADATA_VERSION] = $version;
        }
    }

    /**
     * @return Envelope<T>
     */
    public static function new(Event $message, Domain\Id $producerId, ?int $version = null): self
    {
        return new self(UUID::random(), $message::class, $message, $producerId, $producerId, $version);
    }

    public function uuid(): UUID
    {
        return new UUID($this->get(self::METADATA_UUID));
    }

    public function name(): string
    {
        return $this->get(self::METADATA_NAME);
    }

    /**
     * @return T
     */
    public function message(): Event
    {
        return $this->message;
    }

    public function producerId(): Domain\Id
    {
        $class = $this->get(self::METADATA_PRODUCER_TYPE);

        /** @var class-string<Domain\Id> $class */
        /** @phpstan-var class-string<Domain\Id> $class */
        /** @psalm-var class-string<Domain\Id> $class */
        return $class::fromString($this->get(self::METADATA_PRODUCER_ID));
    }

    public function entityId(): Domain\Id
    {
        $class = $this->get(self::METADATA_ENTITY_TYPE);

        /** @var class-string<Domain\Id> $class */
        /** @phpstan-var class-string<Domain\Id> $class */
        /** @psalm-var class-string<Domain\Id> $class */
        return $class::fromString($this->get(self::METADATA_ENTITY_ID));
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
        if (!\is_scalar($value)) {
            throw new \InvalidArgumentException(sprintf('Value for attribute "%s" is a scalar.', $name));
        }

        $new = new self(
            $this->uuid(),
            $this->name(),
            $this->message(),
            $this->producerId(),
            $this->entityId(),
            $this->version(),
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

    public function defineVersion(int $version): self
    {
        return new self(
            $this->uuid(),
            $this->name(),
            $this->message(),
            $this->producerId(),
            $this->entityId(),
            $version,
        );
    }

    public function defineEntityId(Domain\Id $entityId): self
    {
        return new self(
            $this->uuid(),
            $this->name(),
            $this->message(),
            $this->producerId(),
            $entityId,
            $this->version(),
        );
    }
}
