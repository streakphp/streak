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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Entity;
use Streak\Domain\Event;
use Streak\Domain\Id\UUID;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Envelope
 */
class EnvelopeTest extends TestCase
{
    private Event|MockObject $event1;
    private Event|MockObject $event2;
    private Entity\Id $entityId1;

    protected function setUp(): void
    {
        $this->event1 = $this->getMockBuilder(Event::class)->setMockClassName('dushf9fguiewhfh')->getMockForAbstractClass();
        $this->event2 = $this->getMockBuilder(Event::class)->setMockClassName('y7rb7wfe77fcw7e')->getMockForAbstractClass();
        $this->entityId1 = Event\EnvelopeTest\EntityId::random();
        $this->entityId1 = Event\EnvelopeTest\EntityId::random();
    }

    public function testEnvelope(): void
    {
        $uuid = UUID::random();
        $producerId = UUID::random();
        $envelope1a = Event\Envelope::new($this->event1, $producerId, \PHP_INT_MAX);

        self::assertInstanceOf(Envelope::class, $envelope1a);
        self::assertInstanceOf(UUID::class, $envelope1a->uuid());
        self::assertNotNull($envelope1a->get($envelope1a::METADATA_UUID));
        self::assertSame('dushf9fguiewhfh', $envelope1a->name());
        self::assertSame('dushf9fguiewhfh', $envelope1a->get($envelope1a::METADATA_NAME));
        self::assertTrue($envelope1a->producerId()->equals($producerId));
        self::assertNotNull($envelope1a->get($envelope1a::METADATA_PRODUCER_TYPE));
        self::assertNotNull($envelope1a->get($envelope1a::METADATA_PRODUCER_ID));
        self::assertSame($this->event1, $envelope1a->message());
        self::assertSame(\PHP_INT_MAX, $envelope1a->version());
        self::assertSame(\PHP_INT_MAX, $envelope1a->get($envelope1a::METADATA_VERSION));
        self::assertSame(['uuid' => $envelope1a->uuid()->toString(), 'name' => $envelope1a->name(), 'producer_type' => 'Streak\Domain\Id\UUID', 'producer_id' => $envelope1a->producerId()->toString(), 'entity_type' => 'Streak\Domain\Id\UUID', 'entity_id' => $envelope1a->producerId()->toString(), 'version' => $envelope1a->version()], $envelope1a->metadata());
        self::assertNull($envelope1a->get('attr-1'));

        $envelope1b = $envelope1a->set('attr-1', 'value-1');

        self::assertNotSame($envelope1a, $envelope1b);

        self::assertNull($envelope1a->get('attr-1'));
        self::assertSame(['uuid' => $envelope1a->uuid()->toString(), 'name' => $envelope1a->name(), 'producer_type' => 'Streak\Domain\Id\UUID', 'producer_id' => $envelope1a->producerId()->toString(), 'entity_type' => 'Streak\Domain\Id\UUID', 'entity_id' => $envelope1a->producerId()->toString(), 'version' => $envelope1a->version(), 'attr-1' => 'value-1'], $envelope1b->metadata());
        self::assertSame('value-1', $envelope1b->get('attr-1'));

        $envelope2a = new Event\Envelope($uuid, 'dushf9fguiewhfh', $this->event1, $producerId, $producerId, \PHP_INT_MAX);
        $envelope2b = new Event\Envelope($uuid, 'dushf9fguiewhfh', $this->event1, $producerId, $producerId, \PHP_INT_MAX);

        self::assertInstanceOf(Envelope::class, $envelope2a);
        self::assertEquals($envelope2a->uuid(), $uuid);
        self::assertSame('dushf9fguiewhfh', $envelope2a->name());
        self::assertEquals($envelope2a->producerId(), $producerId);
        self::assertSame($this->event1, $envelope2a->message());
        self::assertSame(\PHP_INT_MAX, $envelope2a->version());
        self::assertSame(['uuid' => $envelope2a->uuid()->toString(), 'name' => $envelope2a->name(), 'producer_type' => 'Streak\Domain\Id\UUID', 'producer_id' => $envelope2a->producerId()->toString(), 'entity_type' => 'Streak\Domain\Id\UUID', 'entity_id' => $envelope2a->producerId()->toString(), 'version' => $envelope2a->version()], $envelope2a->metadata());

        self::assertTrue($envelope1a->equals($envelope1a));
        self::assertTrue($envelope2a->equals($envelope2a));
        self::assertFalse($envelope1a->equals($envelope2a));
        self::assertFalse($envelope2a->equals($envelope1a));
        self::assertTrue($envelope2a->equals($envelope2b));

        self::assertFalse($envelope1a->equals(new \stdClass()));

        $envelope3 = Event\Envelope::new($this->event2, $producerId);
        $envelope3 = $envelope3->defineEntityId($this->entityId1);

        self::assertNull($envelope3->version());
        self::assertSame('y7rb7wfe77fcw7e', $envelope3->name());
        self::assertEquals($envelope3->producerId(), $producerId);
        self::assertEquals($envelope3->entityId(), $this->entityId1);

        $envelope4 = $envelope3->defineVersion(1);

        self::assertTrue($envelope3->equals($envelope4));
        self::assertTrue($envelope4->equals($envelope3));

        self::assertSame(1, $envelope4->version());
        self::assertSame('y7rb7wfe77fcw7e', $envelope4->name());
        self::assertTrue($envelope4->producerId()->equals($producerId));
        self::assertTrue($envelope4->entityId()->equals($this->entityId1));

        $envelope5 = $envelope4->defineVersion(2);

        self::assertSame(2, $envelope5->version());
        self::assertSame('y7rb7wfe77fcw7e', $envelope5->name());
        self::assertTrue($envelope5->producerId()->equals($producerId));
        self::assertTrue($envelope5->entityId()->equals($this->entityId1));

        $envelope6 = $envelope5->defineEntityId($this->entityId1);

        self::assertSame(2, $envelope6->version());
        self::assertSame('y7rb7wfe77fcw7e', $envelope6->name());
        self::assertTrue($envelope6->producerId()->equals($producerId));
        self::assertTrue($envelope6->entityId()->equals($this->entityId1));
    }

    public function testSettingEmptyAttributeName(): void
    {
        $envelope = Event\Envelope::new($this->event1, UUID::random(), \PHP_INT_MAX);

        $exception = new \InvalidArgumentException('Name of the attribute can not be empty.');
        $this->expectExceptionObject($exception);

        $envelope->set('', 'value-1');
    }

    public function nonScalarValues()
    {
        return [
            [[]],
            [new \stdClass()],
        ];
    }

    /**
     * @dataProvider nonScalarValues
     *
     * @param mixed $value
     */
    public function testSettingNonScalarAttribute($value): void
    {
        $envelope = Event\Envelope::new($this->event1, UUID::random(), \PHP_INT_MAX);

        $exception = new \InvalidArgumentException('Value for attribute "attr-1" is a scalar.');
        $this->expectExceptionObject($exception);

        $envelope->set('attr-1', $value);
    }
}

namespace Streak\Domain\Event\EnvelopeTest;

use Streak\Domain\Entity;
use Streak\Domain\Id\UUID;

class EntityId extends UUID implements Entity\Id
{
}
