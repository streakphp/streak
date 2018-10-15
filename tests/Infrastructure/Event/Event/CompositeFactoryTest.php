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

namespace Streak\Infrastructure\Event\Event;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;
use Streak\Domain\Event\Listener;
use Streak\Domain\Event\Listener\Factory;
use Streak\Domain\Exception\InvalidIdGiven;
use Streak\Infrastructure\Event\Listener\CompositeFactory;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Event\Listener\CompositeFactory
 */
class CompositeFactoryTest extends TestCase
{
    /**
     * @var Listener\Id|MockObject
     */
    private $id1;

    /**
     * @var Factory|MockObject
     */
    private $factory1;

    /**
     * @var Factory|MockObject
     */
    private $factory2;

    /**
     * @var Factory|MockObject
     */
    private $factory3;

    /**
     * @var Listener|MockObject
     */
    private $listener1;

    /**
     * @var Event|MockObject
     */
    private $event1;

    protected function setUp()
    {
        $this->id1 = $this->getMockBuilder(Listener\Id::class)->getMockForAbstractClass();

        $this->factory1 = $this->getMockBuilder(Factory::class)->getMockForAbstractClass();
        $this->factory2 = $this->getMockBuilder(Factory::class)->getMockForAbstractClass();
        $this->factory3 = $this->getMockBuilder(Factory::class)->getMockForAbstractClass();

        $this->listener1 = $this->getMockBuilder(Listener::class)->setMethods(['replay', 'reset', 'completed'])->getMockForAbstractClass();

        $this->event1 = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
    }

    public function testEmptyComposite()
    {
        $factory = new CompositeFactory();

        $exception = new InvalidIdGiven($this->id1);
        $this->expectExceptionObject($exception);

        $factory->create($this->id1);
    }

    public function testComposite()
    {
        $factory = new CompositeFactory();
        $factory->add($this->factory1);
        $factory->add($this->factory2);
        $factory->add($this->factory3);

        $this->factory1
            ->expects($this->once())
            ->method('create')
            ->with($this->id1)
            ->willThrowException(new InvalidIdGiven($this->id1))
        ;

        $this->factory2
            ->expects($this->once())
            ->method('create')
            ->with($this->id1)
            ->willReturn($this->listener1)
        ;

        $this->factory3
            ->expects($this->never())
            ->method('create')
            ->with($this->id1)
        ;

        $listener = $factory->create($this->id1);

        $this->assertSame($listener, $this->listener1);

        $this->expectExceptionObject(new \BadMethodCallException());

        $factory->createFor($this->event1);
    }
}
