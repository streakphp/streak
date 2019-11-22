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
use Streak\Domain\Event;
use Streak\Domain\Id\UUID;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Domain\Event\Envelope
 */
class EnvelopeTest extends TestCase
{
    /**
     * @var Event|MockObject
     */
    private $event1;

    protected function setUp()
    {
        $this->event1 = $this->getMockBuilder(Event::class)->setMockClassName('dushf9fguiewhfh')->getMockForAbstractClass();
    }

    public function testEnvelope()
    {
        $uuid = UUID::random();
        $producerId = UUID::random();
        $envelope1a = Event\Envelope::new($this->event1, $producerId, PHP_INT_MAX);

        $this->assertInstanceOf(Envelope::class, $envelope1a);
        $this->assertInstanceOf(UUID::class, $envelope1a->uuid());
        $this->assertSame('dushf9fguiewhfh', $envelope1a->name());
        $this->assertTrue($envelope1a->producerId()->equals($producerId));
        $this->assertSame($this->event1, $envelope1a->message());
        $this->assertSame(PHP_INT_MAX, $envelope1a->version());
        $this->assertEmpty($envelope1a->metadata());
        $this->assertNull($envelope1a->get('attr-1'));

        $envelope1b = $envelope1a->set('attr-1', 'value-1');

        $this->assertNotSame($envelope1a, $envelope1b);

        $this->assertNull($envelope1a->get('attr-1'));
        $this->assertSame(['attr-1' => 'value-1'], $envelope1b->metadata());
        $this->assertSame('value-1', $envelope1b->get('attr-1'));

        $envelope2a = new Event\Envelope($uuid, 'dushf9fguiewhfh', $this->event1, $producerId, PHP_INT_MAX);
        $envelope2b = new Event\Envelope($uuid, 'dushf9fguiewhfh', $this->event1, $producerId, PHP_INT_MAX);

        $this->assertInstanceOf(Envelope::class, $envelope2a);
        $this->assertTrue($envelope2a->uuid()->equals($uuid));
        $this->assertSame('dushf9fguiewhfh', $envelope2a->name());
        $this->assertTrue($envelope2a->producerId()->equals($producerId));
        $this->assertSame($this->event1, $envelope2a->message());
        $this->assertSame(PHP_INT_MAX, $envelope2a->version());
        $this->assertEmpty($envelope2a->metadata());

        $this->assertTrue($envelope1a->equals($envelope1a));
        $this->assertTrue($envelope2a->equals($envelope2a));
        $this->assertFalse($envelope1a->equals($envelope2a));
        $this->assertFalse($envelope2a->equals($envelope1a));
        $this->assertTrue($envelope2a->equals($envelope2b));

        $this->assertFalse($envelope1a->equals(1));
        $this->assertFalse($envelope1a->equals(1.0));
        $this->assertFalse($envelope1a->equals('1'));
        $this->assertFalse($envelope1a->equals([]));
        $this->assertFalse($envelope1a->equals(new \stdClass()));
    }

    public function testSettingEmptyAttributeName()
    {
        $envelope = Event\Envelope::new($this->event1, UUID::random(), PHP_INT_MAX);

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
     */
    public function testSettingNonScalarAttribute($value)
    {
        $envelope = Event\Envelope::new($this->event1, UUID::random(), PHP_INT_MAX);

        $exception = new \InvalidArgumentException('Value for attribute "attr-1" is a scalar.');
        $this->expectExceptionObject($exception);

        $envelope->set('attr-1', $value);
    }
}
