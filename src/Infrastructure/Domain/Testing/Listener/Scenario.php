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

namespace Streak\Infrastructure\Domain\Testing\Listener;

use PHPUnit\Framework\Assert;
use Streak\Application;
use Streak\Domain;
use Streak\Domain\Event;
use Streak\Infrastructure\Domain\Event\InMemoryStream;
use Streak\Infrastructure\Domain\Event\Sourced\Subscription\InMemoryState;
use Streak\Infrastructure\Domain\Testing\Listener\Scenario\Then;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @codeCoverageIgnore
 */
class Scenario implements Scenario\Given, Scenario\When, Scenario\Then, Domain\CommandHandler
{
    private Application\CommandBus $bus;

    private Event\Listener\Factory $factory;

    /**
     * @var Event\Envelope[]
     */
    private array $given = [];

    private ?Event\Envelope $when = null;

    private bool $replaying = false;

    /**
     * @var \Streak\Domain\Command[]
     */
    private array $actualCommands = [];

    /**
     * @var \Streak\Domain\Command[]
     */
    private array $expectedCommands = [];

    /**
     * @var \Throwable[]
     */
    private array $expectedErrors = [];

    public function __construct(Application\CommandBus $bus, Event\Listener\Factory $factory)
    {
        $this->bus = $bus;
        $this->bus->register($this);
        $this->factory = $factory;
    }

    public function given(Domain\Event ...$events): Scenario\When
    {
        $this->given = array_map(fn (Domain\Event $event) => Event\Envelope::new($event, Domain\Id\UUID::random()), $events);

        return $this;
    }

    public function when(Domain\Event $event): Scenario\Then
    {
        $this->when = Event\Envelope::new($event, Domain\Id\UUID::random());

        return $this;
    }

    public function then(Domain\Command $command = null, \Throwable $error = null): Then
    {
        $this->expectedCommands[] = $command;
        $this->expectedErrors[] = $error;

        return $this;
    }

    public function assert(callable $constraint = null): void
    {
        $first = array_shift($this->given);

        if (null !== $first) {
            $listener = $this->factory->createFor($first);

            $this->given = array_merge([$first], $this->given);

            $previousState = null;
            if ($listener instanceof Event\Listener\Stateful) {
                $this->replaying = true;
                foreach ($this->given as $event) {
                    $listener->on($event);

                    $currentState = $listener->toState(InMemoryState::empty());
                    $currentState = InMemoryState::fromState($currentState);

                    if ($currentState->equals($previousState)) {
                        // state not changed
                        continue;
                    }
                    $previousState = $currentState;
                    $previousListener = $listener;

                    $listener = $this->factory->create($previousListener->listenerId());
                    $listener->fromState($currentState);

                    Assert::assertEquals($previousListener, $listener, sprintf('Listener "%s" that listened to %s" and generated incomplete state. Please review your Listener\Stateful::toState() and Listener\Stateful::fromState() methods.', \get_class($listener), \get_class($event)));
                }
                $this->replaying = false;
            }
            $new = $this->factory->createFor($first);
        } else {
            $listener = $this->factory->createFor($this->when);
            $new = $this->factory->createFor($this->when);
        }

        if ($listener instanceof Event\Filterer) {
            $stream = new InMemoryStream($this->when);
            $stream = $listener->filter($stream);
            $stream = iterator_to_array($stream);

            Assert::assertEquals([$this->when], $stream, sprintf('Listener is not listening to %s event.', \get_class($this->when)));
        }

        Assert::assertNotEmpty($this->expectedCommands, 'At least one then() clause is required.');

        $this->expectedCommands = array_filter($this->expectedCommands); // cleanup
        $listener->on($this->when);

        if (!$listener instanceof Event\Listener\Stateful) {
            Assert::assertEquals($listener, $new, sprintf('State introduced when listener "%s" listened to "%s" event, but listener is not implementing "%s" interface.', \get_class($listener), \get_class($this->when), Event\Listener\Stateful::class));
        }

        Assert::assertEquals($this->expectedCommands, $this->actualCommands, 'Expected commands do not match actual commands dispatched by the listener.');

        if (null === $constraint) {
            $constraint = function (Event\Listener $listener): void {
            };
        }

        $constraint($listener);
    }

    public function handleCommand(Domain\Command $command): void
    {
        if (true === $this->replaying) {
            return;
        }

        $this->actualCommands[] = $command;

        $error = array_shift($this->expectedErrors);
        if (null !== $error) {
            throw $error;
        }
    }
}
