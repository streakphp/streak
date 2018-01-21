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

namespace Streak\Infrastructure\Testing\Saga;

use PHPUnit\Framework\Assert;
use Streak\Application;
use Streak\Domain;
use Streak\Infrastructure\CommandBus\SynchronousCommandBus;
use Streak\Infrastructure\Event\InMemoryStream;
use Streak\Infrastructure\Testing\saga\Scenario\Given;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @codeCoverageIgnore
 */
class Scenario implements Scenario\Given, Scenario\When, Scenario\Then, Application\CommandHandler
{
    private $commands = [];
    private $bus;
    private $saga;
    private $factory;
    private $replayed = false;

    public function __construct(SynchronousCommandBus $bus, Application\Saga\Factory $factory)
    {
        $this->bus = $bus;
        $this->factory = $factory;
        $this->bus->register($this);
    }

    public function given(Domain\Event ...$messages) : Scenario\When
    {
        $first = array_shift($messages);

        if (null === $first) {
            return $this;
        }

        $this->saga = $this->factory->createFor($first);
        $this->saga->replay(new InMemoryStream($first, ...$messages));

        return $this;
    }

    public function when(Domain\Event $message) : Scenario\Then
    {
        if (null === $this->saga) {
            $this->saga = $this->factory->createFor($message);
        }

        $this->saga->on($message);

        return $this;
    }

    public function then(Application\Command $command = null) : void
    {
        $expected = [];
        if (null !== $command) {
            $expected = [$command];
        }

        Assert::assertEquals($expected, $this->commands);
    }

    public function handle(Application\Command $command) : void
    {
        $this->commands[] = $command;
    }
}
