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

namespace Streak\Infrastructure\Domain\Event\Event;

use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Application\Event\Listener;
use Streak\Domain\Exception\InvalidIdGiven;
use Streak\Domain\Id\UUID;
use Streak\Infrastructure\Domain\Event\Listener\CompositeFactory;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Domain\Event\Listener\CompositeFactory
 */
class CompositeFactoryTest extends TestCase
{
    private Listener\Id $id1;

    private Listener\Factory $factory1;
    private Listener\Factory $factory2;
    private Listener\Factory $factory3;

    private Listener $listener1;

    private Event\Envelope $event1;

    protected function setUp(): void
    {
        $this->id1 = $this->getMockBuilder(Listener\Id::class)->getMockForAbstractClass();

        $this->factory1 = $this->getMockBuilder(Listener\Factory::class)->getMockForAbstractClass();
        $this->factory2 = $this->getMockBuilder(Listener\Factory::class)->getMockForAbstractClass();
        $this->factory3 = $this->getMockBuilder(Listener\Factory::class)->getMockForAbstractClass();

        $this->listener1 = $this->getMockBuilder(Listener::class)->addMethods(['replay', 'reset', 'completed'])->getMockForAbstractClass();

        $this->event1 = Event\Envelope::new($this->getMockBuilder(Event::class)->getMockForAbstractClass(), UUID::random(), 1);
    }

    public function testEmptyComposite(): void
    {
        $factory = new CompositeFactory();

        $exception = new InvalidIdGiven($this->id1);
        $this->expectExceptionObject($exception);

        $factory->create($this->id1);
    }

    public function testComposite(): void
    {
        $factory = new CompositeFactory();
        $factory->add($this->factory1);
        $factory->add($this->factory2);
        $factory->add($this->factory3);

        $this->factory1
            ->expects(self::once())
            ->method('create')
            ->with($this->id1)
            ->willThrowException(new InvalidIdGiven($this->id1))
        ;

        $this->factory2
            ->expects(self::once())
            ->method('create')
            ->with($this->id1)
            ->willReturn($this->listener1)
        ;

        $this->factory3
            ->expects(self::never())
            ->method('create')
            ->with($this->id1)
        ;

        $listener = $factory->create($this->id1);

        self::assertSame($listener, $this->listener1);

        $this->expectExceptionObject(new \BadMethodCallException());

        $factory->createFor($this->event1);
    }
}
