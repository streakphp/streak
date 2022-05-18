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
 * @template TMessage as Event
 * @implements Domain\Envelope<TMessage>
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

    /**
     * @var-phpstan non-empty-array<non-empty-string, scalar>
     * @var-psalm non-empty-array<non-empty-string, scalar>&array{
     *          uuid: non-empty-string,
     *          name: non-empty-string,
     *          producer_type: class-string<Domain\Id>,
     *          producer_id: non-empty-string,
     *          entity_type: class-string<Domain\Id>,
     *          entity_id: non-empty-string,
     *          version?: positive-int,
     * }
     */
    private array $metadata;

    /**
     * @param non-empty-string $name
     * @param TMessage $message
     */
    public function __construct(UUID $uuid, string $name, private Event $message, Domain\Id $producerId, Domain\Id $entityId, ?int $version = null)
    {
        $this->metadata = [
            self::METADATA_UUID => $uuid->toString(),
            self::METADATA_NAME => $name,
            self::METADATA_PRODUCER_TYPE => $producerId::class,
            self::METADATA_PRODUCER_ID => $producerId->toString(),
            self::METADATA_ENTITY_TYPE => $entityId::class,
            self::METADATA_ENTITY_ID => $entityId->toString(),
        ];
        if (null !== $version) {
            $this->metadata[self::METADATA_VERSION] = $version;
        }
    }

    /**
     * @template TEvent of Event
     * @param TEvent $message
     *
     * @return self<TEvent>
     */
    public static function new(Event $message, Domain\Id $producerId, ?int $version = null): self
    {
        return new self(UUID::random(), $message::class, $message, $producerId, $producerId, $version);
    }

    public function uuid(): UUID
    {
        return new UUID($this->metadata[self::METADATA_UUID]);
    }

    /**
     * @return non-empty-string
     */
    public function name(): string
    {
        return $this->metadata[self::METADATA_NAME];
    }

    public function message()
    {
        return $this->message;
    }

    public function producerId(): Domain\Id
    {
        $class = $this->metadata[self::METADATA_PRODUCER_TYPE];
        $id = $this->metadata[self::METADATA_PRODUCER_ID];

        /** @var class-string<Domain\Id> $class */
        return $class::fromString($id);
    }

    public function entityId(): Domain\Id
    {
        $class = $this->metadata[self::METADATA_ENTITY_TYPE];
        $id = $this->metadata[self::METADATA_ENTITY_ID];

        /** @var class-string<Domain\Id> $class */
        return $class::fromString($id);
    }

    public function version(): ?int
    {
        return $this->metadata[self::METADATA_VERSION] ?? null;
    }

    /**
     * @param non-empty-string $name
     * @return self<TMessage>
     */
    public function set(string $name, bool|float|int|string $value): self
    {
        if (empty($name)) { // @phpstan-ignore-line
            throw new \InvalidArgumentException('Name of the attribute can not be empty.');
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

    /**
     * @return self<TMessage>
     */
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

    /**
     * @return self<TMessage>
     */
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
