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

namespace Streak\Infrastructure\Testing\Listener;

use PHPUnit\Framework\Assert;
use Streak\Application;
use Streak\Domain;
use Streak\Domain\Event;
use Streak\Infrastructure\CommandBus\SynchronousCommandBus;
use Streak\Infrastructure\Event\InMemoryStream;
use Streak\Infrastructure\Testing\Listener\Scenario\Then;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @codeCoverageIgnore
 */
class Scenario implements Scenario\Given, Scenario\When, Scenario\Then, Application\CommandHandler
{
    /**
     * @var Application\Command[]
     */
    private $dispatchedCommands = [];

    /**
     * @var Application\CommandBus
     */
    private $bus;

    /**
     * @var Event\Listener|Event\Replayable|Event\Listener\Completable
     */
    private $listener;

    /**
     * @var Event\Listener\Factory
     */
    private $factory;

    /**
     * @var Event
     */
    private $when;

    /**
     * @var \Throwable[]
     */
    private $expectedErrors = [];

    /**
     * @var Application\Command[]
     */
    private $expectedCommands = [];

    /**
     * @var bool
     */
    private $expectedCompletion;

    public function __construct(SynchronousCommandBus $bus, Event\Listener\Factory $factory)
    {
        $this->bus = $bus;
        $this->factory = $factory;
        $this->bus->register($this);
    }

    public function given(Domain\Event ...$events) : Scenario\When
    {
        $first = array_shift($events);

        if (null === $first) {
            return $this;
        }

        $this->listener = $this->factory->createFor($first);

        if ($this->listener instanceof Event\Replayable) {
            $this->listener->replay(new InMemoryStream($first, ...$events));

            return $this;
        }

        Assert::assertEmpty($events, 'Listener is not replayable. Only first starting event is expected for such listener.');

        return $this;
    }

    public function when(Domain\Event $event) : Scenario\Then
    {
        if (null === $this->listener) {
            $this->listener = $this->factory->createFor($event);
        }

        $this->when = $event;

        return $this;
    }

    public function then(Application\Command $command = null, \Throwable $error = null) : Then
    {
        $this->expectedErrors[] = $error;
        $this->expectedCommands[] = $command;

        return $this;
    }

    public function completed(bool $completed) : Then
    {
        $this->expectedCompletion = $completed;

        return $this;
    }

    public function run() : void
    {
        if ($this->listener instanceof Event\Filterer) {
            $stream = new InMemoryStream($this->when);
            $stream = $this->listener->filter($stream);
            $stream = iterator_to_array($stream);

            Assert::assertEquals([$this->when], $stream, sprintf('Listener is not listening to %s event.', get_class($this->when)));
        }

        $this->expectedCommands = array_filter($this->expectedCommands);
        $this->listener->on($this->when);

        Assert::assertEquals($this->expectedCommands, $this->dispatchedCommands);
        if (null !== $this->expectedCompletion) {
            Assert::assertInstanceOf(Event\Listener\Completable::class, $this->listener, 'Listener is not completable.');
            Assert::assertSame($this->expectedCompletion, $this->listener->completed());
        }
    }

    public function handle(Application\Command $command) : void
    {
        $this->dispatchedCommands[] = $command;

        $error = array_shift($this->expectedErrors);
        if (null !== $error) {
            throw $error;
        }
    }
}
