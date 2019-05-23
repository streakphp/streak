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

namespace Streak\Application\Listener\Subscriptions\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Application\Listener\Subscriptions\Projector;
use Streak\Domain\Clock;
use Streak\Domain\Event;
use Streak\Domain\Event\Exception\InvalidEventGiven;
use Streak\Domain\Exception\InvalidIdGiven;
use Streak\Domain\Id\UUID;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Application\Listener\Subscriptions\Projector\Factory
 * @covers \Streak\Application\Listener\Subscriptions\Projector::correlate
 */
class FactoryTest extends TestCase
{
    /**
     * @var Connection|MockObject
     */
    private $connection;

    /**
     * @var Clock|MockObject
     */
    private $clock;

    /**
     * @var Event\Envelope|MockObject
     */
    private $event;

    protected function setUp()
    {
        $this->connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
        $this->clock = $this->getMockBuilder(Clock::class)->getMockForAbstractClass();
        $this->event = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $this->event = Event\Envelope::new($this->event, UUID::random());
    }

    public function testCreate()
    {
        $factory = new Factory($this->connection, $this->clock);

        $id = new Id();
        $projector = $factory->create($id);

        $this->assertInstanceOf(Projector::class, $projector);
        $this->assertSame($id, $projector->id());
    }

    public function testCreateFor()
    {
        $factory = new Factory($this->connection, $this->clock);

        $event = new Event\Sourced\Subscription\Event\SubscriptionStarted($this->event, new \DateTimeImmutable());
        $event = Event\Envelope::new($event, UUID::random());

        $projector = $factory->createFor($event);

        $this->assertInstanceOf(Projector::class, $projector);
    }

    public function testCreateFromInvalidId()
    {
        $invalidId = $this->getMockBuilder(Event\Listener\Id::class)->getMockForAbstractClass();

        $exception = new InvalidIdGiven($invalidId);
        $this->expectExceptionObject($exception);

        $factory = new Factory($this->connection, $this->clock);

        $factory->create($invalidId);
    }

    public function testCreateFromInvalidEvent()
    {
        $invalid = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
        $invalid = Event\Envelope::new($invalid, UUID::random());

        $exception = new InvalidEventGiven($this->event);
        $this->expectExceptionObject($exception);

        $factory = new Factory($this->connection, $this->clock);

        $factory->createFor($invalid);
    }
}
