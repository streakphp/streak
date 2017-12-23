<?php

declare(strict_types=1);

/**
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Infrastructure\Testing\Saga;

use PHPUnit\Framework\Assert;
use Streak\Application;
use Streak\Domain;
use Streak\Infrastructure\CommandBus\SynchronousCommandBus;

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
    private $replayed = false;

    public function __construct(SynchronousCommandBus $bus, Application\Saga\Factory $factory)
    {
        $this->bus = $bus;
        $this->saga = $factory->create();
        $this->bus->register($this);
    }

    public function given(Domain\Message ...$messages) : Scenario\When
    {
        $first = array_shift($messages);

        if (null === $first) {
            return $this;
        }

        Assert::assertTrue($this->saga->beginsWith($first), 'Saga should begin its lifecycle with first given message.');

        $this->saga->replay($first, ...$messages);

        $this->replayed = true;

        return $this;
    }

    public function when(Domain\Message $message) : Scenario\Then
    {
        if (false === $this->replayed) {
            Assert::assertTrue($this->saga->beginsWith($message), 'Saga should begin its lifecycle with first given message.');
        }

        $this->saga->on($message, $this->bus);

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
