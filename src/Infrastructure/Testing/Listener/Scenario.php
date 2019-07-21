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
     * @var Application\CommandBus
     */
    private $bus;

    /**
     * @var Event\Listener\Factory
     */
    private $factory;

    /**
     * @var Event[]
     */
    private $given = [];

    /**
     * @var Event
     */
    private $when;

    /**
     * @var Application\Command[]
     */
    private $actualCommands = [];

    /**
     * @var Application\Command[]
     */
    private $expectedCommands = [];

    /**
     * @var \Throwable[]
     */
    private $expectedErrors = [];

    public function __construct(Application\CommandBus $bus, Event\Listener\Factory $factory)
    {
        $this->bus = $bus;
        $this->bus->register($this);
        $this->factory = $factory;
    }

    public function given(Domain\Event ...$events) : Scenario\When
    {
        $this->given = $events;

        return $this;
    }

    public function when(Domain\Event $event) : Scenario\Then
    {
        $this->when = $event;

        return $this;
    }

    public function then(Application\Command $command = null, \Throwable $error = null) : Then
    {
        $this->expectedCommands[] = $command;
        $this->expectedErrors[] = $error;

        return $this;
    }

    public function assert(callable $constraint = null) : void
    {
        $first = array_shift($this->given);

        if (null !== $first) {
            $listener = $this->factory->createFor($first);

            if ($listener instanceof Event\Replayable) {
                $listener->replay(new InMemoryStream($first, ...$this->given));
            }
        } else {
            $listener = $this->factory->createFor($this->when);
        }

        if ($listener instanceof Event\Filterer) {
            $stream = new InMemoryStream($this->when);
            $stream = $listener->filter($stream);
            $stream = iterator_to_array($stream);

            Assert::assertEquals([$this->when], $stream, sprintf('Listener is not listening to %s event.', get_class($this->when)));
        }

        Assert::assertNotEmpty($this->expectedCommands, 'At least one then() clause is required.');

        $this->expectedCommands = array_filter($this->expectedCommands); // cleanup
        $listener->on($this->when);

        Assert::assertEquals($this->expectedCommands, $this->actualCommands, 'Expected commands do not match actual commands dispatched by the listener.');

        if (null === $constraint) {
            $constraint = function (Event\Listener $listener) {};
        }

        $constraint($listener);
    }

    public function handle(Application\Command $command) : void
    {
        $this->actualCommands[] = $command;

        $error = array_shift($this->expectedErrors);
        if (null !== $error) {
            throw $error;
        }
    }
}
